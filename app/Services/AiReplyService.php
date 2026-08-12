<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates AI-assisted reply drafts for patron inquiries using Google Gemini.
 *
 * Calls the Gemini REST API directly through Laravel's Http client, so no
 * extra composer package is required and the same code runs on Railway.
 */
class AiReplyService
{
    /**
     * Generate a personalized reply draft for a patron inquiry.
     *
     * @param  array  $inquiry  Patron + inquiry context (patron_name, event_type, venue, theme_motif, date, time, status, message)
     * @return string
     *
     * @throws \RuntimeException when the API key is missing or the request fails
     */
    public static function generateDraft(array $inquiry): string
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey) || str_starts_with($apiKey, 'your_')) {
            throw new \RuntimeException(
                'GEMINI_API_KEY is not configured. Add it to the Railway variables (or your local .env) to use AI drafts.'
            );
        }

        $model = config('services.gemini.model', 'gemini-2.5-flash');

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'system_instruction' => ['parts' => [['text' => self::systemPrompt()]]],
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => self::buildPrompt($inquiry)]]],
                        ],
                        'generationConfig' => [
                            // Gemini 3.x are reasoning models: they spend tokens on internal
                            // "thoughts" before answering. 4096 is a safe ceiling — a real reply
                            // draft uses ~400-800 tokens, so this never truncates a normal answer
                            // while keeping worst-case per-request usage low on the free tier.
                            'maxOutputTokens' => 4096,
                        ],
                    ]
                );

            $body = $response->json();

            if (! $response->successful() || empty($body['candidates'][0]['content']['parts'])) {
                $error = $body['error']['message'] ?? $response->body();
                Log::error('Gemini draft generation failed', ['error' => $error, 'status' => $response->status()]);

                throw new \RuntimeException('AI draft generation failed: '.$error);
            }

            // The model may split its answer across multiple parts — join them all.
            $draft = '';
            foreach ($body['candidates'][0]['content']['parts'] as $part) {
                if (! empty($part['text'])) {
                    $draft .= $part['text'];
                }
            }

            // Log actual token usage so free-tier consumption can be monitored.
            $usage = $body['usageMetadata'] ?? [];
            Log::info('Gemini draft generated', [
                'model' => $model,
                'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                'output_tokens' => $usage['candidatesTokenCount'] ?? 0,
                'thought_tokens' => $usage['thoughtsTokenCount'] ?? 0,
                'total_tokens' => $usage['totalTokenCount'] ?? 0,
            ]);

            return trim($draft);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini connection error: '.$e->getMessage());

            throw new \RuntimeException('Could not reach the AI service. Please check your internet connection and try again.');
        }
    }

    /**
     * System instruction that grounds the model in Villa Salud's business facts.
     */
    protected static function systemPrompt(): string
    {
        return <<<'PROMPT'
You are the professional customer-service assistant of Villa Salud Catering Services, a Filipino catering and events venue business.

Business facts you must know:
- Two event venues: Villa I (up to 200 pax) and Villa II (up to 300 pax). Villa II has built-in crystal chandeliers and a mirror carpet; guests who book it get a free upgrade to ghost chairs.
- Private swimming pool for rent, good for up to 50 pax.
- Accredited coordinator team: "The Events by Design (TED)", owned by Ms. Rhose and Sir. Cris.
- Venue-only rentals are allowed EXCEPT during the whole month of December.
- Office is open daily except holidays, 9:00 AM to 6:00 PM. Ocular visits must be scheduled by contacting the office first.
- Contact email: villasaludcateringservices@gmail.com
- Packages are available on the website's home page.

Rules:
- Write a warm, professional, and concise reply email (2 to 4 short paragraphs).
- Address the patron by their first name if available.
- Acknowledge their specific inquiry (event type, venue, theme/motif, date) to show it was actually read.
- Answer their questions using the business facts above when possible; never invent prices, promotions, or policies that are not listed above.
- If important information is missing, politely ask for the additional details or invite them to email, visit the office, or schedule a call.
- Sign off as "Villa Salud Catering Services".
- Do NOT write a subject line. Do NOT use placeholders like [name] or [date]. Use the actual details from the inquiry.
PROMPT;
    }

    /**
     * Builds the user prompt from the actual inquiry context.
     */
    protected static function buildPrompt(array $inquiry): string
    {
        $details = [];

        foreach ([
            'Patron name'   => $inquiry['patron_name'] ?? null,
            'Event type'    => $inquiry['event_type'] ?? null,
            'Venue'         => $inquiry['venue'] ?? null,
            'Theme / motif' => $inquiry['theme_motif'] ?? null,
            'Event date'    => $inquiry['date'] ?? null,
            'Event time'    => $inquiry['time'] ?? null,
            'Status'        => $inquiry['status'] ?? null,
            'Inquiry message' => $inquiry['message'] ?? null,
        ] as $label => $value) {
            if (! empty($value)) {
                $details[] = "{$label}: {$value}";
            }
        }

        return "Write the reply email for the following customer inquiry.\n\n"
            .implode("\n", $details)
            ."\n\nUse only the business facts provided. Be polite, warm, and helpful.";
    }
}
