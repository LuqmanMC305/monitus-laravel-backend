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
            'title' => 'required|string|max:255',
            'instruction' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer',
            'severity' => 'required|string',
        ]);

        // 2. Save the Alert to DB
        $alert = Alert::create([
            'admin_id' => Auth::id(), // Get current Jetstream user ID
            'title' => $validated['title'],
            'instruction' => $validated['instruction'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius' => $validated['radius'],
            'severity' => $validated['severity'],
            'status' => 'active',
            /* FUTURE: For Community Intelligence (Crowdsourcing)
            'report_id' => $request->report_id ?? null, 
            'is_community_verified' => $request->has('report_id')
            */
        ]);

        // 3. Trigger the Geo-Engine Logic 
        // (Find Users Within Radius of Recently Saved Alert)
        $sql = "ST_DWithin(last_location, ST_MakePoint(?, ?)::geography, ?)";
        $bindings = [$alert->longitude, $alert->latitude, $alert->radius];
        $affectedUsers = MobileUser::whereRaw($sql, $bindings)
            ->where('last_location_at', '>=', now()->subMinutes(30))
            ->get();

        foreach ($affectedUsers as $user)
        {

            // Access the pivot data
            $community_pivot = $user->pivot;
            
            // Attach user to the alert
            $alert->mobileUsers()->attach($user->mobile_user_id, [
                'is_success' => true,
                'delivered_at' => now(),
            ]);

            // Telegram Direct Broadcast
            // Only send if user has linked to Telegram
            if($user->is_telegram_verified && $user->telegram_chat_id && $community_pivot->status === 'approved')
                {
                    try{
                        // Use helper function 'sendDirectAlert'
                        $this->telegramService->sendDirectAlert($user->telegram_chat_id, $alert);
                    } catch(\Exception $e){
                        Log::error("Telegram Direct Fail. User: {$user->mobile_user_id}, ChatID: {$user->telegram_chat_id}. Error: " . $e->getMessage());
                    }
                }
        }

        // Alert relevant communities (Community Group Sweep) 
        $affectedCommunities = Community::whereRaw(
            "ST_DWithin(community_location, ST_SetSRID(ST_Point(?, ?), 4326)::geography, ?)",
            [$alert->longitude, $alert->latitude, $alert->radius]
        )->get();

        info("Broadcast Sweep: Found " . $affectedCommunities->count() . " communities within range.");

        foreach($affectedCommunities as $community)
        {
            if ($community->telegram_group_id)
                {
                    try {
                            $result = $this->telegramService->sendCommunityAlert(
                            $community->telegram_group_id,
                            $alert // Passing the object to the Formatter submodule
                        );

                        // 2. Log the outcome using your new Model
                        CommunityBroadcast::create([
                            'alert_id' => $alert->alert_id,
                            'community_id' => $community->community_id,
                            'community_status' => $result ? 'success' : 'failed',
                            'telegram_message_id' => $result->message_id ?? null, // Captures ID if available
                            'error_log' => $result ? null : 'Failed to reach Telegram API',
                        ]);

                        if($result) $notifiedCount++;

                    } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
                        // Log specific Telegram errors without stopping the script
                        Log::warning("Telegram API Error for Community {$community->community_id}: " . $e->getMessage());
                    } catch (\Exception $e){
                        error("Telegram Community Fail. Community: {$community->community_name}, GroupID: {$community->telegram_group_id}. Error: " . $e->getMessage());
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
            'status' => 'NEW_ALERT',
            'latitude'   => (string)$alert->latitude,  // Matches Flutter key
            'longitude'  => (string)$alert->longitude, // Matches Flutter key
            'radius'     => (string)$alert->radius,
            'alert_type' => $alert->severity,          // Or 'emergency'
        ]; 

        
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
            'message' => 'Alert broadcasted successfully!',
            'alert_id' => $alert->alert_id,
            'notified_count' => $affectedUsers->count(),
            'tokens_found' => $tokens, // Now you will see this in Edge!
            'debug_user_ids' => $affectedUsers->pluck('mobile_user_id'),
            'search_radius' => $alert->radius,
            'latitude' => $alert->latitude,
            'longitude' => $alert->longitude,
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
