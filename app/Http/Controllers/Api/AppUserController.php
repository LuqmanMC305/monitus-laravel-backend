<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\MobileUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; 

class AppUserController extends Controller
{
    /**
     * Handle the initial creation of a standard mobile citizen account.
     */
    public function register(Request $request) 
    {
        // Validate the incoming text input from Flutter
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:app_users,app_user_email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();
    
        //  FIX 2: Restored actual user generation so accounts can actually be created!
        $user = AppUser::create([
            'app_user_name' => $data['name'],
            'app_user_email' =>  $data['email'],
            'app_user_password' => Hash::make($data['password']), // Secure Password Encryption
        ]);

        // Return the clean app_user_id right back to your RegistrationService screen
        return response()->json([
            'message' => 'Registration successful',
            'app_user_id' => (int) $user->app_user_id
        ], 201);
    }
   
    /**
     * Authenticate session credentials and establish secure hardware pivot linkage.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Verify the user profile exists
        $user = AppUser::where('app_user_email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->app_user_password)) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        // Create a secure Sanctum token session string
        $token = $user->createToken('mobile-app-token')->plainTextToken;

        // Fetch the Mobile User hardware row lookup
        $mobileUser = MobileUser::where('app_user_id', $user->app_user_id)->first();

        // FIX 3: Dynamic fallback generation! If this account has no device row yet, 
        // create a safe database entry on the fly so 'mobile_user_id' is NEVER null again.
        if (!$mobileUser) {
            $mobileUser = MobileUser::create([
                'app_user_id' => $user->app_user_id,
                'device_id' => 'initial_login_placeholder_' . $user->app_user_id,
                'fcm_token' => '',
                // Provide default geometric coordinates so PostGIS queries run perfectly
                'last_location' => DB::raw("ST_GeogFromText('SRID=4326;POINT(100.3036 5.3763)')"),
                'last_location_at' => now(),
            ]);
        }

        // Return the clean unified response payload contract back to Postman & Flutter
        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token, 
            'token_type' => 'Bearer',
            'app_user_id' => (int) $user->app_user_id, 
            'mobile_user_id' => (int) $mobileUser->mobile_user_id, // 🟢 Guaranteed Integer!
            'name' => $user->app_user_name
        ], 200);
    }
}