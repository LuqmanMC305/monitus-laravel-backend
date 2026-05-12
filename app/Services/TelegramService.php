<?php

namespace App\Services;

use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    // For Automated Alerts (Takes an Object)
    public function sendCommunityAlert($groupId, $alert)
    {
        //  Message Formatter
        $formattedMessage = $this->formatAlertMessage($alert);
        return $this->executeSendMessage($groupId, $formattedMessage);
   
    }
    /**
     * Message Formatter
     * Converts raw alert data into a professional Telegram layout.
     */
    private function formatAlertMessage($alert)
    {
        $severityIcons = [
            'HIGH' => '🚨',
            'MEDIUM' => '⚠️',
            'LOW' => 'ℹ️'
        ];

        $icon = $severityIcons[strtoupper($alert->severity)] ?? '📢';

        return "<b>{$icon} MONITUS ALERT: {$alert->title}</b>\n\n" .
               "<b>Instruction:</b>\n<i>{$alert->instruction}</i>\n\n" .
               "📍 <a href='https://monitus.app/view/{$alert->alert_id}'>View on Interactive Map</a>\n" .
               "<i>Stay safe and follow local authority guidance.</i>";
    }

    //  For Manual Announcements (Takes a String)
    public function sendManualAnnouncement($groupId, $communityName, $message)
    {
            $formatted = "📢 <b>OFFICIAL COMMUNITY ANNOUNCEMENT</b>\n" .
                        "<b>Group:</b> {$communityName}\n\n" .
                        $message . "\n\n" .
                        "<i>Sent via Monitus Command Centre</i>";

            return $this->executeSendMessage($groupId, $formatted);
    }

    public function sendDirectAlert($chatId, $alert)
    {
        $formatted = "🚨 <b>" . strtoupper($alert->severity) . " PERSONAL ALERT: {$alert->title}</b>\n\n" .
                    "{$alert->instruction}\n\n" .
                    "<i>Stay alert in your current location.</i>";

        return $this->sendCommunityAlert($chatId, $formatted);
    }

    // 3. THE DISPATCHER (Private Engine)
    // This is the only place that actually talks to the Telegram API
    private function executeSendMessage($groupId, $text)
    {
        try {
            return Telegram::sendMessage([
                'chat_id' => $groupId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ]);
        } catch (\Exception $e) {
            Log::error("Telegram Dispatcher Failure for Chat {$groupId}: " . $e->getMessage());
            return false;
        }
    }

    /* FUTURE FEATURE: Community-Verified Triggering (CROWDSOURCING) 
    public function sendWardenVerificationRequest($wardenChatId, $report)
    {
        // Logic to send a private message to a Warden to ask them to verify a report
    }
    */
}