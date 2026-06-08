<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\Request;
use App\Services\FCMService;
use App\Models\Community;
use App\Models\CommunityUser;
use App\Models\IncidentReport;
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

        // 5. Count Rejected Alert Requests
        $rejectedAlertRequests = IncidentReport::where('status','rejected')->count();

        // Generate arrays for the last 14 days chronologically
        $labels = [];
        $data = [];
        
        $maxDays = 13;

        for ($i = $maxDays; $i >= 0; $i--) {
            $date = now()->subDays($i);
            // Format label as "30 May" or "31 May"
            $labels[] = $date->format('d M'); 
            
            // Count how many verified alerts were broadcasted on that specific calendar date
            $approvedAlertCount = IncidentReport::where('status', 'approved') // or your active broadcast condition
                ->whereDate('created_at', $date->toDateString())
                ->count();

            // Count how many direct system alerts were broadcasted on this same calendar date
            $broadcastedAlertsCount = Alert::whereDate('created_at', $date->toDateString())
                ->count();
            
            $data[] = $broadcastedAlertsCount + $approvedAlertCount;
        }

        // FOR LOG STREAM
        // 1. Fetch latest active alerts (Approved but not yet resolved)
        $activeAlertsLogs = IncidentReport::where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'active_alert',
                    'title' => 'Active Emergency Broadcast',
                    'description' => "Live alert active: \"{$item->description}\" near coordinates {$item->latitude}, {$item->longitude}.",
                    'time' => $item->created_at,
                ];
            });

        // 2. Fetch latest pending community join/membership requests
        $pendingCommunityLogs = CommunityUser::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'community_request',
                    'title' => 'Pending Community Access Request',
                    'description' => "User ID #{$item->user_id} has requested membership access verification approval.",
                    'time' => $item->created_at,
                ];
            });

        // 3. Fetch latest resolved incident history records
        $resolvedHistoryLogs = IncidentReport::where('status', 'resolved')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'resolved_history',
                    'title' => 'Incident Safely Resolved',
                    'description' => "Emergency report ID #{$item->id} has been marked as fully resolved by the dispatcher.",
                    'time' => $item->updated_at, // Tracks when it was marked resolved
                ];
            });

        // 4. Merge all collections together and sort them completely chronologically
        $dashboardStream = collect()
            ->merge($activeAlertsLogs)
            ->merge($pendingCommunityLogs)
            ->merge($resolvedHistoryLogs)
            ->sortByDesc('time')
            ->take(8); // Limit the scroll pane view container frame to the top 8 recent items

        return view('dashboard', compact(
            'activeCount',
            'resolvedCount', 
            'totalAlerts', 
            'highSeverity',
            'recentAlerts',
            'highAlerts',
            'medAlerts',
            'lowAlerts',
            'labels',
            'data',
            'rejectedAlertRequests',
            'dashboardStream'
        ));


    }
}
