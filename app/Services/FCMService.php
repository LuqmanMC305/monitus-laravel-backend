<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\AndroidConfig;

class FCMService
{
    protected $messaging;

   public function sendEmergencyAlert($tokens, $title, $body, array $extraData = [])
    {
        info("FCM DATA PAYLOAD:", $extraData);
        info("TEST", $extraData);
        // Accessing Messaging Instance Via Firebase Facades
        $messaging = Firebase::messaging();
        $sentCount = 0;

        foreach ($tokens as $token) {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body)) //
                ->withAndroidConfig([
                    'priority' => 'high', 
                    'notification' => [
                         'channel_id' => 'high_importance_channel', // CRITICAL: Matches your test
                    ],       
                ])
                // If 'alert_type' is already in $extraData, we just pass it straight through.
                // If you want to keep 'emergency' as a separate channel routing key for Flutter, 
                // you can add a brand new key like 'message_purpose' => 'emergency'.
                ->withData(array_merge([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK', // Helps Flutter open the app on tap
                    ], $extraData)) 
                ->toToken($token);

            $messaging->send($message);
            $sentCount++;
        }
        return $sentCount;
    }
}