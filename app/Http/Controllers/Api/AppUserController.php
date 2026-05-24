<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\MobileUser;
use Illuminate\Http\Request;
use  Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class AppUserController extends Controller
{
    public function register(Request $request)
    {
        //  Fallback to app_user_id if user_id is missing, and sanitize literal "null" strings
        $rawUserId = $request->input('user_id') ?? $request->input('app_user_id');
        
        if (empty($rawUserId) || $rawUserId === 'null') {
            return response()->json([
                'status' => 'error',
                'message' => 'A valid numeric user identity is required for sync.'
            ], 400);
        }

        // Explicitly merge the cleaned integer back so validation succeeds
        $request->merge(['user_id' => (int) $rawUserId]);

        $validated = $request->validate([
            'user_id'   => 'required|integer|exists:app_users,app_user_id',
            'device_id' => 'required|string',
            'fcm_token' => 'required|string',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        Log::info("Background Location Sync Triggered!", $request->all());

        $user = MobileUser::updateOrCreate(
            ['device_id' => $validated['device_id']], 
            [
                'app_user_id'      => $validated['user_id'],
                'fcm_token'        => $validated['fcm_token'], 
                'last_location'    => DB::raw("ST_GeogFromText('SRID=4326;POINT({$validated['longitude']} {$validated['latitude']})')"),
                'last_location_at' => now(), 
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'User location synchronized successfully.',
            'data' => [
                'id' => $user->mobile_user_id,
                'updated_at' => now()->toDateTimeString()
            ]
        ], 201);
    }
   
    public function login(Request $request)
    {
        // Verify credentials
        $request->validate
        ([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Verify the user exists and the hashed password matches
        $user = AppUser::where('app_user_email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->app_user_password)) 
        {
            return response()->json
            ([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        // Create a new token for this session
        $token = $user->createToken('mobile-app-token')->plainTextToken;

        // Fetch the Mobile User ID (The missing link for your pivot table)
        // This finds ID 7 for Egal (app_user_id 20)
        $mobileUser = \App\Models\MobileUser::where('app_user_id', $user->app_user_id)->first();

        // Return the app_user_id so the phone can save it
        return response()->json
        ([
            'message' => 'Login successful',
            'access_token' => $token, 
            'token_type' => 'Bearer',
            'app_user_id' => (int) $user->app_user_id, // ID 20
            'mobile_user_id' => $mobileUser ? (int) $mobileUser->mobile_user_id : null, // ID 7
            'name' => $user->app_user_name
        ], 200);

    }
}
