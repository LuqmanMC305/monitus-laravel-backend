<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\IncidentReport;

class LLMValidationService
{
    public function checkSpam(IncidentReport $report): void
    {
        $apiKey = config('services.llm.key') ?? env('GEMINI_API_KEY');
        
        if (!$apiKey) {
            Log::warning("LLM Spam Check bypassed: No API key configured.");
            return;
        }

        try {
            // 🟢 Instructing the LLM to return a strict, clean JSON structure
            $prompt = "You are an AI triage assistant for a community-based emergency monitoring system called Monitus. " .
                      "Analyse the following user-submitted public incident report description for nonsense, spam, " .
                      "or testing gibberish (e.g., 'test 123', 'hello world', keyboard smashes, or completely irrelevant text).\n\n" .
                      "Description: \"{$report->incident_description}\"\n\n" .
                      "Respond ONLY with a valid JSON object matching this structure: " .
                      "{\"is_spam\": true/false, \"confidence\": 0.00-1.00}";

            // Example targeting the fast, cost-effective Gemini Flash endpoint
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $resultText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                // Clean markdown backticks if the LLM accidentally wraps the JSON output
                $cleanJson = trim(str_replace(['```json', '```'], '', $resultText));
                $data = json_decode($cleanJson, true);

                if (isset($data['is_spam'])) {
                    // Update your holding pen fields directly
                    $report->update([
                        'llm_spam_score' => $data['confidence'] ?? 0.50,
                        'status' => $data['is_spam'] === true ? 'rejected' : 'pending'
                    ]);

                    Log::info("LLM Spam Check completed for Report ID {$report->id}: Status is now {$report->status}");
                }
            }
        } catch (\Exception $e) {
            Log::error("LLM Spam Check failed for Report ID {$report->id}: " . $e->getMessage());
            // Failsafe: leave it as pending for manual admin review if the API drops out
        }
    }
}
