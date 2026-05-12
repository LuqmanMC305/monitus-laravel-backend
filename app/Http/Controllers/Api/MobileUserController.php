<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\FCMService;
use Illuminate\Support\Facades\Auth;


class MobileUserController extends Controller
{
    /**
     * Handle the registration and location update for mobile users.
     */
    public function register(Request $request)
    {
        // 1. Request Validation
        $validated = $request->validate([
            'user_id' => 'required|exists:app_users,app_user_id',
            'device_id'=> 'required|string',
            'fcm_token' => 'required|string',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        Log::info("Background Update Hit!", $request->all());

        // 2. Data Transformation & Persistence
        // Use updateOrCreate to prevent duplicate entries for the same device.
        $user = MobileUser::updateOrCreate(
            [
                // Find this id (WHERE clause in SQL)
                'device_id' => $validated['device_id'],

            ], 
            [
                // Then, fill or update these columns 
                'app_user_id' => $validated['user_id'],
                'fcm_token'=> $validated['fcm_token'], 
                // Convert Lat/Long to PostGIS Geography Point
                'last_location' => DB::raw("ST_GeogFromText('SRID=4326;POINT({$validated['longitude']} {$validated['latitude']})')"),
                'last_location_at' => now(), // The "Timestamp" context
            ]
        );

        // 3. API Response
        return response()->json([
            'status' => 'success',
            'message' => 'User location synchronized successfully.',
            'data' => [
                'id' => $user->mobile_user_id,
                'updated_at' => $user->last_location_at->toDateTimeString()
            ]
        ], 201);
    }

    public function login (Request $request)
    {
        $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        ]);

        // Use Auth to check credentials
        if (Auth::attempt(['app_user_email' => $credentials['email'], 'password' => $credentials['password']])) {
            $user = Auth::user();
            return response()->json([
                'status' => 'success',
                'app_user_id' => $user->app_user_id,
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

}
