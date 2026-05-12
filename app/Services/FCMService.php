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
                ->withData(array_merge(['alert_type' => 'emergency'], $extraData)) // $extraData = latitude, longitude, radius, alert_type
                ->toToken($token);

            $messaging->send($message);
            $sentCount++;
        }
        return $sentCount;
    }
}