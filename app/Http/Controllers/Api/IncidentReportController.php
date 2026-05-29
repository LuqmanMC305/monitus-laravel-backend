<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentReport;
use App\Models\Alert;
use App\Models\MobileUser;
use App\Services\FCMService; // 🟢 Direct access to your notification broadcaster
use Illuminate\Support\Facades\Http;

class IncidentReportController extends Controller
{
    protected $fcmService;

    // Inject your working FCM service directly into this controller constructor
    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * API Endpoint for Flutter: Citizen Submits a Report
     */
    public function store(Request $request)
    {
        $request->validate([
            'app_user_id' => 'required',
            'incident_description' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'nullable|image|max:5120', // 5MB Limit Max
        ]);

        // Handle physical image storage using standard public uploads layout
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('reports', 'public');
        }

        // Create the holding pen record
        $report = IncidentReport::create([
            'app_user_id' => $request->app_user_id,
            'incident_description' => $request->incident_description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'image_path' => $imagePath,
            'status' => 'pending',
        ]);

        // 🧠 OPTIONAL: Call your automated LLM gate check right here!
        // $this->runLLMSpamCheck($report);

        return response()->json(['message' => 'Report received successfully', 'report_id' => $report->id], 201);
    }

    /**
     * Web Route for Blade Dashboard: Admin Approves a Report and Broadcasts it
     */
    public function approve($id)
    {
        $report = IncidentReport::findOrFail($id);
        
        // 1. Update the holding pen status record
        $report->update(['status' => 'approved']);

        // 2. Automatically generate an official broadcast record in the alerts table
        $alert = Alert::create([
            'admin_id' => auth()->id() ?? 1, // Fallback to main admin ID account context
            'title' => '⚠️ Community Reported Incident',
            'instruction' => $report->incident_description,
            'severity' => 'MEDIUM', // Default crowd assignment weight
            'area_type' => 'radius',
            'radius' => 500, // Pre-configured radius circle sweep boundaries
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'status' => 'active',
            'category_icon' => '📢',
            'alert_category' => 'general'
        ]);

        // 3. Gather active tokens within proximity using your established PostGIS logic
        $tokens = MobileUser::whereRaw(
            "ST_DWithin(last_location, ST_MakePoint(?, ?)::geography, ?)",
            [$alert->longitude, $alert->latitude, $alert->radius]
        )->pluck('fcm_token')->toArray();

        // 4. Fire the cloud broadcast signals straight to mobile phones!
        if (!empty($tokens)) {
            $extraData = [
                'status' => 'NEW_ALERT',
                'alert_type' => $alert->severity,
                'area_type' => $alert->area_type,
                'latitude' => (string)$alert->latitude,
                'longitude' => (string)$alert->longitude,
                'radius' => (string)$alert->radius,
                'category_icon' => $alert->category_icon,
            ];

            $this->fcmService->sendEmergencyAlert(
                $tokens, 
                $alert->title, 
                $alert->instruction, 
                $extraData
            );
        }

        return redirect()->back()->with('success', 'Incident approved and broadcasted successfully!');
    }
}