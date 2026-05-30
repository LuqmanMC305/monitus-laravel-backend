<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\IncidentReport;

class LLMValidationService
{
    public function checkSpam(IncidentReport $report): bool
    {
        $apiKey = config('services.llm.key') ?: env('GEMINI_API_KEY');
        
        if (!$apiKey) {
            Log::warning("LLM Spam Check bypassed: No API key configured.");
            return false;
        }

        try {
            $prompt = "You are an AI triage assistant for a community-based emergency monitoring system called Monitus. " .
                    "Analyse the following user-submitted public incident report description for nonsense, spam, " .
                    "or testing gibberish (e.g., 'test 123', 'hello world', keyboard smashes, or completely irrelevant text).\n\n" .
                    "Description: \"{$report->incident_description}\"\n\n" .
                    "Respond ONLY with a valid JSON object matching this structure: " .
                    "{\"is_spam\": true/false, \"confidence\": 0.00-1.00}";

           // Using 3.5 Flash
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json' // Forces clean JSON formatting natively!
                    ]
                ]);

            Log::info('Gemini Status', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $resultText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                // 🟢 DEBUG 1: Look at exactly what the Gemini endpoint returned!
                Log::info("RAW GEMINI TEXT OUTPUT: " . $resultText);

                // 🟢 FIX: Robust regex to extract everything between the first '{' and the last '}'
                // This strips away any markdown wrapper code automatically, regardless of spacing!
                if (preg_match('/\{.*\}/s', $resultText, $matches)) {
                    $cleanJson = $matches[0];
                } else {
                    $cleanJson = $resultText;
                }

                $data = json_decode($cleanJson, true);

                // 🟢 DEBUG 2: Look at the successfully parsed associative array map properties
                Log::info("PARSED GEMINI ARRAY:", ['data' => $data]);

                if ($data && isset($data['is_spam'])) {
                    // Force convert the model assignment explicitly
                    $report->llm_spam_score = (float) ($data['confidence'] ?? 0.50);
                    $report->status = $data['is_spam'] === true ? 'rejected' : 'pending';
                    
                    // Save directly bypassing potential mass-assignment blockades
                    $report->save();

                    Log::info("LLM Spam Check successful for Report ID {$report->id}: Score is {$report->llm_spam_score}, Status is {$report->status}");
                    return true;
                }
                
                Log::error("JSON parsing structure format mismatch for Report ID {$report->id}");
            } else {
                Log::error("Gemini API Error Response HTTP Status: " . $response->status() . " Body: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("LLM Spam Check failed for Report ID {$report->id}: " . $e->getMessage());
        }

        return false;
    }
}
