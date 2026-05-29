<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentReport;
use App\Models\Alert;
use App\Models\MobileUser;
use App\Services\FCMService; 
use Illuminate\Support\Facades\Auth;// 
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
            'admin_id' => Auth::id(), // Fallback to main admin ID account context
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

        // One call handles everything perfectly!
        app(AlertDistributionService::class)->broadcast($alert);
      

        return redirect()->back()->with('success', 'Incident approved and broadcasted successfully!');
    }
}