<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\Request;
use App\Services\FCMService;
use App\Models\Community;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;

class IncidentMapController extends Controller
{
    // Index Method
    public function index()
    {
        // Fetch the Latest Alerts
        $latestNum = 10; // 10 Latest Alerts
        $alerts = Alert::where('status','active')
                        ->latest()
                        ->take($latestNum)
                        ->get();
        
        $communities = Community::all();

        return view('admin.incident-map', 
            compact('alerts'),
            compact('communities')
        );

        /*
        $alerts = Alert::latest()->take($latestNum)->get();
        
        */
    }

    /**
     * Update the alert status to 'resolved' from Axios PATCH request.
     */

    public function resolve($id)
    {
        // Update Server DB
        $alert = Alert::findOrFail($id);
        $alert->status = 'resolved';
        $alert->save();

        // Prepare FCM Message for Mobile Client
        $messaging = Firebase::messaging();

        // Find tokens for users who were originally notified by this alert
        // Note: Adjust 'fcm_token' to match  actual MobileUser column name
        $tokens = \App\Models\MobileUser::whereHas('alerts', function($q) use ($id) {
            $q->where('delivery_logs.alert_id', $id); 
        })->pluck('fcm_token')->filter()->toArray();

        if (!empty($tokens)) {
            $message = CloudMessage::new()
                ->withData([
                    'type' => 'RESOLVE_ALERT',
                    'alert_title' => $alert->title, // Use title to match the local SQLite key
                    'alert_id' => (string)$alert->alert_id,
                    'status' => 'resolved'
                ]);

            // This sends the data silently (no notification popup)
            $messaging->sendMulticast($message, $tokens);
    }

        return response()->json([
            'success' => true,
            'message' => 'Alert ' . $id . ' has been resolved and signal sent to mobile clients.'
        ]);
    }

    public function manage()
    {
        // Fetch all active alerts for admin control
        $activeAlerts = Alert::where('status', 'active')
                            ->latest()
                            ->get();

        // Fetch recently resolved alerts for the history section
        define('RESOLVE_ALERT_NUM', 10);

        $resolvedAlerts = Alert::where('status', 'resolved')
                            ->latest()
                            ->take(RESOLVE_ALERT_NUM)
                            ->get();


        return view('admin.manage-alerts', compact('activeAlerts', 'resolvedAlerts'));
    }

    public function dashboard()
    {
        // 1. Get counts for metric cards
        $activeCount = Alert::where('status', 'active')->count();
        $resolvedCount = Alert::where('status', 'resolved')->count();
        $totalAlerts = Alert::count();

        // 2. Get Severity Breakdown for chart
        $highSeverity = Alert::where('status', 'active')->where('severity', 'HIGH')->count();

        // 3. Get Recent 5 Alerts
        define('RECENT_ALERT_NUM', 5);
        $recentAlerts = Alert::latest()->take(RECENT_ALERT_NUM)->get();

        // 4. Get Alerts Counts by Severity
        $highAlerts = Alert::where('severity', 'HIGH')->count();
        $medAlerts = Alert::where('severity', 'MEDIUM')->count();
        $lowAlerts = Alert::where('severity', 'LOW')->count();

        return view('dashboard', compact(
            'activeCount',
            'resolvedCount', 
            'totalAlerts', 
            'highSeverity',
            'recentAlerts',
            'highAlerts',
            'medAlerts',
            'lowAlerts'
        ));
    }
}
