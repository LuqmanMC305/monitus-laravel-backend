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
use App\Services\AlertDistributionService;

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

        // ==========================================
        // SECTION WHICH CALLS THE ALERT DISTRIBUTION SERVICE FILE
        // ==========================================
        
        //  Call the service to handle spatial checks, logging, and broadcasts
        app(AlertDistributionService::class)->broadcast($alert);

        //  Return your response (Your existing code)
        return response()->json(['message' => 'Alert broadcasted successfully'], 201);

    }

}
