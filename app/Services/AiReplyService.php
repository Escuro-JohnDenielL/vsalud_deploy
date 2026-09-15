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
        return self::generate(self::systemPrompt(), self::buildPrompt($inquiry), 'reply draft');
    }

    /**
     * Generate a concise internal "Event Brief" for a reservation so the admin and
     * the coordinator team can prep for an event at a glance (who, what, when,
     * where, plus any special requests captured by the booking form).
     *
     * @param  array  $context  Reservation + booking-form context (client_name, client_email,
     *                          client_contact, tracking_code, event_type, venue, theme_motif,
     *                          date, time, status, message, form_data)
     * @return string
     *
     * @throws \RuntimeException when the API key is missing or the request fails
     */
    public static function generateEventBrief(array $context): string
    {
        return self::generate(self::eventBriefSystemPrompt(), self::buildEventBriefPrompt($context), 'event brief');
    }

    /**
     * Core Gemini request shared by every AI helper (reply drafts, event briefs, ...).
     */
    protected static function generate(string $systemPrompt, string $userPrompt, string $label = 'draft'): string
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey) || str_starts_with($apiKey, 'your_')) {
            throw new \RuntimeException(
                'GEMINI_API_KEY is not configured. Add it to the Railway variables (or your local .env) to use AI features.'
            );
        }

        $model = config('services.gemini.model', 'gemini-3.5-flash');

        try {
            // Gemini 3.x are reasoning models and can be slow on the free tier —
            // a 30s timeout caused spurious "Could not reach the AI service"
            // errors on Railway. Allow up to 90s for the full generation.
            $response = Http::timeout(90)
                ->connectTimeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => $userPrompt]]],
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
                Log::error("Gemini {$label} generation failed", ['error' => $error, 'status' => $response->status()]);

                throw new \RuntimeException('AI '.$label.' generation failed: '.$error);
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
            Log::info("Gemini {$label} generated", [
                'model' => $model,
                'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                'output_tokens' => $usage['candidatesTokenCount'] ?? 0,
                'thought_tokens' => $usage['thoughtsTokenCount'] ?? 0,
                'total_tokens' => $usage['totalTokenCount'] ?? 0,
            ]);

            return trim($draft);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini connection error: '.$e->getMessage());

            // Guzzle maps a request timeout (cURL 28) to ConnectException, which
            // Laravel wraps as ConnectionException — same class as a real network
            // failure. Distinguish them so admins don't get a misleading message.
            if (str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'cURL error 28')) {
                throw new \RuntimeException('The AI service took too long to respond. Please try again in a moment.');
            }

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

    /**
     * System instruction for the internal "Event Brief" — grounded in the same
     * Villa Salud business facts but tuned for an at-a-glance staff handoff.
     */
    protected static function eventBriefSystemPrompt(): string
    {
        return <<<'PROMPT'
You are the events-coordinator assistant of Villa Salud Catering Services, a Filipino catering and events venue business. You produce concise internal handoff briefs so the admin and the accredited coordinator team can prep for an event at a glance.

Business facts you must know:
- Two event venues: Villa I (up to 200 pax) and Villa II (up to 300 pax). Villa II has built-in crystal chandeliers and a mirror carpet; guests who book it get a free upgrade to ghost chairs.
- Private swimming pool for rent, good for up to 50 pax.
- Accredited coordinator team: "The Events by Design (TED)", owned by Ms. Rhose and Sir. Cris.
- Venue-only rentals are allowed EXCEPT during the whole month of December.
- Office is open daily except holidays, 9:00 AM to 6:00 PM.

Rules:
- Write ONE compact, plain-text Event Brief of 5 to 8 short lines (roughly 70 to 120 words total). No markdown, no bullet symbols, no headers, no subject line.
- Organize each line as "Label: value" (for example "Event:", "Client:", "Date & time:", "Venue:", "Headcount:", "Theme/motif:", "Special requests:", "Notes:").
- Start with the event type and the client's family/group name so staff instantly know what it is.
- Pull headcount, food/AV/decor needs, and special requests ONLY from the booking-form data provided.
- Explicitly flag anything staff must prepare or confirm (coordinator needed, early setup, pool rental, late end time, December booking, free ghost-chair upgrade, etc.).
- Never invent prices, packages, headcounts, requests, or contact details. If a detail is missing write "Not specified" — do not guess.
- Keep it internal and neutral. This is NOT a message to the client.
PROMPT;
    }

    /**
     * Builds the user prompt from the actual reservation + booking-form context.
     */
    protected static function buildEventBriefPrompt(array $context): string
    {
        $details = [];

        foreach ([
            'Client name'    => $context['client_name'] ?? null,
            'Client email'   => $context['client_email'] ?? null,
            'Client contact' => $context['client_contact'] ?? null,
            'Tracking code'  => $context['tracking_code'] ?? null,
            'Event type'     => $context['event_type'] ?? null,
            'Venue'          => $context['venue'] ?? null,
            'Event date'     => $context['date'] ?? null,
            'Event time'     => $context['time'] ?? null,
            'Theme / motif'  => $context['theme_motif'] ?? null,
            'Status'         => $context['status'] ?? null,
            'Client message' => $context['message'] ?? null,
        ] as $label => $value) {
            if (! empty($value)) {
                $details[] = "{$label}: {$value}";
            }
        }

        // Include the dynamic booking-form answers (if any).
        $formData = $context['form_data'] ?? [];
        if (is_string($formData)) {
            $formData = json_decode($formData, true) ?: [];
        }

        if (is_array($formData)) {
            foreach ($formData as $key => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                if ($value !== null && $value !== '') {
                    $details[] = ucfirst(str_replace('_', ' ', (string) $key)).': '.$value;
                }
            }
        }

        return "Write the internal Event Brief for the following reservation.\n\n"
            .implode("\n", $details)
            ."\n\nUse only the reservation and booking-form details provided.";
    }
}
