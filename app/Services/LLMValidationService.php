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

        // Model priority hierarchy list
        $models = 
        [
            'gemini-3.5-flash', 
            'gemini-3.1-flash-lite', 
            'gemini-2.5-flash', 
        ];

        $response = null;
        $successfulModel = null;

        $prompt = "You are an AI triage assistant for a community-based emergency monitoring system called Monitus. " .
                    "Analyse the following user-submitted public incident report description for nonsense, spam, " .
                    "or testing gibberish (e.g., 'test 123', 'hello world', keyboard smashes, or completely irrelevant text).\n\n" .
                    "Description: \"{$report->incident_description}\"\n\n" .
                    "Respond ONLY with a valid JSON object matching this structure: " .
                    "{\"is_spam\": true/false, \"confidence\": 0.00-1.00}";

        foreach($models as $model){
            try {
                Log::info("Attempting LLM Spam Check for Report ID {$report->id} using model: {$model}");

               
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, [
                        'contents' => [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json' // Forces clean JSON formatting natively!
                        ]
                    ]);

                if ($response->successful()) {
                    $successfulModel = $model;
                    break;
                }

                // If we get a 503 or any server error, log a warning and let the loop continue to the next model
                Log::warning("Model {$model} failed with status code: " . $response->status() . ". Trying fallback model...");

            } catch (\Exception $e){
                Log::error("Network exception encountered for model {$model}: " . $e->getMessage());
            }              
        }

        try {
            if ($response && $response->successful()) {
                Log::info('Gemini Success Status', [
                    'model_used' => $successfulModel,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $resultText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                Log::info("RAW GEMINI TEXT OUTPUT: " . $resultText);

                if (preg_match('/\{.*\}/s', $resultText, $matches)) {
                    $cleanJson = $matches[0];
                } else {
                    $cleanJson = $resultText;
                }

                $data = json_decode($cleanJson, true);
                Log::info("PARSED GEMINI ARRAY:", ['data' => $data]);

                if ($data && isset($data['is_spam'])) {
                    $report->llm_spam_score = (float) ($data['confidence'] ?? 0.50);
                    $report->status = $data['is_spam'] === true ? 'rejected' : 'pending';
                    $report->save();

                    Log::info("LLM Spam Check successful for Report ID {$report->id}: Score is {$report->llm_spam_score}, Status is {$report->status}");
                    return true;
                }
                
                Log::error("JSON parsing structure format mismatch for Report ID {$report->id}");
            } else {
                $finalStatus = $response ? $response->status() : 'No Response';
                Log::error("All fallback LLM models exhausted. Final Status: {$finalStatus}");
            }
        } catch (\Exception $e) {
            Log::error("LLM Spam Check final processing failed for Report ID {$report->id}: " . $e->getMessage());
        }

        return false;
    }

}
