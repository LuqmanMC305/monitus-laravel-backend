<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppUserController;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Http\Controllers\Api\MobileUserController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\CommunityController;
use App\Models\MobileUser;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/hello', function (Request $request) {
    return response()->json([
        'message' => 'Hello from Laravel',
        'value' => '55'
    ]);
});

// This maps the URL 'your-domain.com/api/register-mobile' to your controller method
Route::post('/register-mobile', [MobileUserController::class, 'register']);

// Hardware/Telemetry Routes (Device syncing and location)
Route::post('/send-alert', [MobileUserController::class, 'sendAlert']);

// Identity Routes (Account creation and verification)
Route::post('/app-register', [AppUserController::class, 'register']);
Route::post('/app-login', [AppUserController::class, 'login']);

// Allow mobile users join communities
Route::middleware('auth:sanctum')->group(function () {
    // This connects your Flutter 'joinCommunity' call to the logic
    Route::post('/communities/join', [CommunityController::class, 'join']);

    // Fetch list of communities
    Route::get('/communities', [CommunityController::class, 'index']);
});

// Test Handshake
Route::get('/test-handshake', function (App\Services\FCMService $service) {
    $user = App\Models\MobileUser::find(5);
    $result = $service->sendEmergencyAlert([$user->fcm_token], "Handshake Test", "Is Flutter listening?");
    return "Service returned: " . $result;
});

// Temporarily Route to Test Alert by Visiting URL in Laptop Browser
Route::get('/test-push', function () {
    $messaging = Firebase::messaging();
    
    // Get the token for User 5
    $user = MobileUser::where('mobile_user_id', 5)->first();

    if (!$user || !$user->fcm_token) {
        return "No token found for User 5!";
    }


    // Create the Emergency Message
    $message = CloudMessage::new()
        ->withNotification(Notification::create(
                'Monitus Flood Alert!', 
                'Emergency: Heavy flooding detected in your current zone. Move to high ground.'
            ))
        ->withAndroidConfig([
        'notification' => [
            'channel_id' => 'high_importance_channel', // Match your Flutter code
            ],
        ])
        ->withData(['alert_type' => 'flood_warning']) // For future logic
        ->toToken( $user->fcm_token);
    
    // Send it!
    $messaging->send($message);

    return "Alert sent to User 5 at " . now();
});



/*
Route::post('/hello', function (Request $request) {
    return response()->json([
        'message'=> 'You sent: ' . $request->input('text'),
        'author' => 'The Author: ' . $request->input('author')
        ]);
        
});

//Other Post Method Example

Route::post('/hello/create', function (Request $request) {
    return response()->json([
        'id' => rand(1, 100000),
        'message'=> 'You sent: ' . $request->input('text'),
        'author' => 'The Author: ' . $request->input('author')
        ]);
        
});

Route::put('/hello/{id}', function ($id, Request $request) {
    return response()->json([
        'id'=> $id,
        'updated' => $request->all()
        ]);
        
});

Route::patch('/hello/{id}', function ($id, Request $request) {
    return response()->json([
        'id'=> $id,
        'patched' => $request->all()
        ]);
        
});

Route::delete('/hello/{id}', function ($id, Request $request) {
    return response()->json([
        'message' => "Deleted resource with id of $id"
        ]);
        
});

*/
