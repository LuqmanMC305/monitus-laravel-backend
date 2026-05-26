<?php

namespace App\Http\Controllers\Api;

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

use function Laravel\Prompts\error;

class AlertController extends Controller
{
    protected $telegramService;

    // Inject the Service into the Controller
    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    // For Manual Targeting
    public function broadcastToCommunity(Request $request)
    {
        // Validate the input from your form
        $validated = $request->validate([
            'community_id' => 'required|exists:communities,community_id',
            'message' => 'required|string|max:500',
        ]);

        // Find community
        $community = Community::findOrFail($validated['community_id']);

        // Send broadcast
        try {
            // Pass raw data to TelegramService
            $this->telegramService->sendManualAnnouncement(
                $community->telegram_group_id,
                $community->community_name,
                $validated['message']
            );

            return back()->with('success', 'Announcement sent to ' . $community->community_name);

        } catch (\Exception $e){
            Log::error("Manual Broadcast Fail: " . $e->getMessage());
            return back()->with('error', 'Failed to reach Telegram.');
        }
           
    }
    public function store(Request $request)
    {

        $notifiedCount = 0;

        // 1. Validate incoming map data
        $validated = $request->validate([

            // Basic Announcement Info
            'title'                   => 'required|string|max:255',
            'instruction'             => 'required|string',
            'severity'                => 'required|string',

            // Classification & Visuals
            'alert_category'          => 'required|string',
            'category_icon'           => 'required|string|max:50',

            // Spatial Control Fields
            'area_type'               => 'required|string|in:radius,polygon',
            
            // Geometric Circle Inputs (Required only if area_type is 'radius')
            'latitude'                => 'required_if:area_type,radius|nullable|numeric',
            'longitude'               => 'required_if:area_type,radius|nullable|numeric',
            'radius'                  => 'required_if:area_type,radius|nullable|integer',

            // Custom Polygon Inputs (Required only if area_type is 'polygon')
            'danger_zone_coordinates' => 'required_if:area_type,polygon|nullable|array',
   
        ]);

            // 2. Save the Alert to DB
        $alert = Alert::create([
            'admin_id'                => Auth::id(), 
            'title'                   => $validated['title'],
            'instruction'             => $validated['instruction'],
            'severity'                => $validated['severity'],
            'status'                  => 'active',
            //  NEW FIELDS HERE:
            'area_type'               => $validated['area_type'],
            'alert_category'          => $validated['alert_category'],
            'category_icon'           => $validated['category_icon'],
            
            // Explicit conditional mapping: keep coordinates safe based on toggle mode
            'latitude'                => $validated['area_type'] === 'radius' ? $validated['latitude'] : null,
            'longitude'               => $validated['area_type'] === 'radius' ? $validated['longitude'] : null,
            'radius'                  => $validated['area_type'] === 'radius' ? $validated['radius'] : null,
            'danger_zone_coordinates' => $validated['area_type'] === 'polygon' ? $validated['danger_zone_coordinates'] : null,
        ]);

        // 3. Trigger the Geo-Engine Logic 
        // (Find Users Within Radius of Recently Saved Alert)
        // 3. Trigger the Geo-Engine Logic
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

        // 4. Return JSON response to Frontend (Axios Library)
        return response()->json([
            'message'                 => 'Alert broadcasted successfully!',
            'alert_id'                => $alert->alert_id,
            'area_type'               => $alert->area_type,
            'alert_category'          => $alert->alert_category,
            'category_icon'           => $alert->category_icon,
            'danger_zone_coordinates' => $alert->danger_zone_coordinates,
            'notified_count'          => $affectedUsers->count(),
            'tokens_found'            => $tokens,
            'fcm_success_count'       => $sentCount,
            'debug_user_ids'          => $affectedUsers->pluck('mobile_user_id'),
            'search_radius'           => $alert->radius,
            'latitude'                => $alert->latitude,
            'longitude'               => $alert->longitude,
        ]);

    }

    /* FUTURE: For Community Intelligence (Crowdsourcing)
    public function escalateReport (Request $request, $reportId)
    {

        // 1. Logic to find the original report (Assume you've created a Report model)
        // $report = Report::findOrFail($reportId);

        // 2. Reuse the existing store logic by redirecting to it
        // or manually trigger the broadcast logic here.
        
        // For your FYP, it's cleaner to create a private helper function 
        // called 'executeBroadcast($alert)' that both 'store' and 
        // 'escalateReport' can call.
    }
    */
}
