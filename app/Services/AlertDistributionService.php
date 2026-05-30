<?php

namespace App\Services;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\MobileUser;
use App\Models\Community;
use App\Models\CommunityBroadcast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;

class AlertDistributionService
{
    protected $fcmService;
    protected $telegramService;

    // Inject your existing services
    public function __construct(FCMService $fcmService, TelegramService $telegramService)
    {
        $this->fcmService = $fcmService;
        $this->telegramService = $telegramService;
    }

    public function broadcast(Alert $alert)
    {
        $notifiedCount = 0;
        $affectedUsers = collect();
        $affectedCommunities = collect();

        if ($alert->area_type === 'radius') {
            // ==========================================
            // 🌐 MODE A: CIRCULAR RADIUS SWEEP (Existing)
            // ==========================================
            $userSql = "ST_DWithin(last_location, ST_MakePoint(?, ?)::geography, ?)";
            $userBindings = [$alert->longitude, $alert->latitude, $alert->radius];
            
            $affectedUsers = MobileUser::whereRaw($userSql, $userBindings)
                ->where('last_location_at', '>=', now()->subMinutes(30))
                ->get();

            $affectedCommunities = Community::whereRaw(
                "ST_DWithin(community_location, ST_SetSRID(ST_Point(?, ?), 4326)::geography, ?)",
                [$alert->longitude, $alert->latitude, $alert->radius]
            )->get();

        } else {
            // ==========================================
            // 📐 MODE B: CUSTOM POLYGON SHAPE SWEEP
            // ==========================================
            // 1. Build a valid WKT (Well-Known Text) POLYGON string from JSON coordinates
            // Expected JSON format: [[lat, lng], [lat, lng], ...]
            $coords = $alert->danger_zone_coordinates;
            
            if (!empty($coords) && is_array($coords)) {
                $wktPoints = [];
                foreach ($coords as $coord) {
                    // WKT format uses: LONGITUDE LATITUDE (No commas between numbers)
                    $wktPoints[] = $coord[1] . ' ' . $coord[0];
                }
                
                // PostGIS requires a polygon to close completely (First point must equal last point)
                if (end($coords) !== $coords[0]) {
                    $wktPoints[] = $coords[0][1] . ' ' . $coords[0][0];
                }
                
                $wktPolygon = "POLYGON((" . implode(',', $wktPoints) . "))";

                // 2. Query users matching the geographic bounding shape
                $affectedUsers = MobileUser::whereRaw(
                    "ST_Contains(ST_GeomFromText(?, 4326)::geometry, last_location::geometry)",
                    [$wktPolygon]
                )->where('last_location_at', '>=', now()->subMinutes(30))->get();

                // 3. Query communities matching the geographic bounding shape
                $affectedCommunities = Community::whereRaw(
                    "ST_Contains(ST_GeomFromText(?, 4326)::geometry, community_location::geometry)",
                    [$wktPolygon]
                )->get();
            }
        }

        // ==========================================
        // 🚀 DISTRIBUTION PIPELINE (Kept Identical)
        // ==========================================
        info("Broadcast Sweep: Found " . $affectedCommunities->count() . " communities within range.");

        foreach ($affectedUsers as $user) {
            // Access the pivot data safely if it exists, fallback if broadcasting generally
            $community_pivot = $user->pivot;
            
            // Attach user to the alert
            $alert->mobileUsers()->attach($user->mobile_user_id, [
                'is_success' => true,
                'delivered_at' => now(),
            ]);

            // Telegram Direct Broadcast
            // Failsafe validation wrapper check
            $isApproved = $community_pivot ? ($community_pivot->status === 'approved') : true;

            if ($user->is_telegram_verified && $user->telegram_chat_id && $isApproved) {
                try {
                    $this->telegramService->sendDirectAlert($user->telegram_chat_id, $alert);
                } catch (\Exception $e) {
                    Log::error("Telegram Direct Fail. User: {$user->mobile_user_id}. Error: " . $e->getMessage());
                }
            }
        }

        foreach ($affectedCommunities as $community) {
            if ($community->telegram_group_id) {
                try {
                    $result = $this->telegramService->sendCommunityAlert(
                        $community->telegram_group_id,
                        $alert
                    );

                    // Log outcome to database log
                    CommunityBroadcast::create([
                        'alert_id' => $alert->alert_id,
                        'community_id' => $community->community_id,
                        'community_status' => $result ? 'success' : 'failed',
                        'telegram_message_id' => $result->message_id ?? null,
                        'error_log' => $result ? null : 'Failed to reach Telegram API',
                    ]);

                    if ($result) $notifiedCount++;

                } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
                    Log::warning("Telegram API Error for Community {$community->community_id}: " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error("Telegram Community Fail. Community: {$community->community_name}. Error: " . $e->getMessage());
                }
            }
        }
            
        // Extract Tokers from Notifier Service
        $tokens = $affectedUsers->pluck('fcm_token')->filter()->toArray();

        Log::info('AFFECTED USERS', $affectedUsers->toArray());
    

        // Call the Notifier Service (Pass the dynamic data)
        $fcmservice = app(FCMService::class);

        // Prepare the data payload (READY TO FOLLOW CAP PROTOCOL)
        $extraData = [
            'status'         => 'NEW_ALERT',
            'alert_type'     => $alert->severity,
            'area_type'      => $alert->area_type,
            'alert_category' => $alert->alert_category,
            'category_icon'  => $alert->category_icon,
            
            // Protect against casting nulls by using string fallbacks
            'latitude'  => (string)($alert->latitude ?? '0.0'),  
            'longitude' => (string)($alert->longitude ?? '0.0'), 
            'radius'    => (string)($alert->radius ?? '0'),
            
            // Encode the array to a JSON string so your Flutter onMessage handler can decode it smoothly
            'danger_zone_coordinates' => $alert->danger_zone_coordinates ? json_encode($alert->danger_zone_coordinates) : '',
        ];


        // Captures the value returned by FCMService class
        $sentCount = $fcmservice->sendEmergencyAlert(
            $tokens, 
            $alert->title, 
            $alert->instruction,
            $extraData
        );

        // Print raw alert data on laravel log
        info('Raw Alert Data:', $extraData);

       // Return a raw data array. Let the controllers decide the view/response!
        return [
            'notified_users_count' => $affectedUsers->count(),
            'notified_groups_count' => $notifiedCount,
            'fcm_success_count'    => $sentCount,
            'tokens'               => $tokens,
        ];


    }
}
