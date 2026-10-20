<?php

namespace App\Http\Controllers\Patron;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Rules\EmailAddress;
use App\Support\FeedbackInput;
use App\Support\PatronAudit;
use App\Support\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class FeedbackController extends Controller
{
    public function index()
    {
        return view('patron.feedback');
    }

    public function store(Request $request)
    {
        // 1. Honeypot. A human never sees or fills this field. A bot that does
        //    gets the same success response a real submission gets, and no row
        //    is written, so the bot has nothing to learn from the reply.
        if (FeedbackInput::honeypotTripped($request->input('hp_ref'))) {
            SecurityLog::honeypot('feedback.store', $request->ip());

            return redirect()->back()->with('success', 'Thank you for your feedback!');
        }

        // 2. Encrypted start time. Too fast means a script, too old means a
        //    stale or replayed form. Both are refused without writing a row.
        $startedAt = $this->decodeStartedAt($request->input('form_started_at'));

        if (! FeedbackInput::isFillAgeAcceptable($startedAt, now()->timestamp)) {
            SecurityLog::staleTimestamp('feedback.store', $request->ip());

            return redirect()->back()
                ->withInput($request->except(['hp_ref', 'form_started_at', '_token']))
                ->withErrors(['message' => 'This form has expired. Please reload the page and try again.']);
        }

        // Strip control characters before validating, so a stray one from a
        // paste does not look like an attack, and so nothing but clean text is
        // ever stored. Newlines are kept in the message body only.
        $request->merge([
            'name' => FeedbackInput::cleanLine((string) $request->input('name', '')),
            'email' => FeedbackInput::cleanLine((string) $request->input('email', '')),
            'message' => FeedbackInput::cleanMessage((string) $request->input('message', '')),
        ]);

        // Shape and length only. Attack strings are not blacklisted; output
        // escaping is what keeps them inert. Letters, marks and digits plus
        // space, comma, period, hyphen and straight or curly apostrophes keep
        // names like O'Brien, Anne-Marie and Jose Maria passing.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:' . FeedbackInput::NAME_PATTERN],
            'email' => ['required', 'string', 'max:150', new EmailAddress()],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Feedback::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'message' => $validated['message'],
        ]);

        // Feedback carries no tracking code, so the entry is identified by the
        // action and the IP only. Honeypot and stale-form submissions are
        // refused above and stay in the security log instead.
        PatronAudit::log(
            'Patron Feedback Submitted',
            $request,
            null,
            'feedback submitted'
        );

        return redirect()->back()->with('success', 'Thank you for your feedback!');
    }

    /**
     * Decode the encrypted form start time, or null when missing or tampered.
     */
    private function decodeStartedAt(mixed $token): ?int
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        try {
            $plain = Crypt::decryptString($token);
        } catch (\Throwable $e) {
            return null;
        }

        return ctype_digit($plain) ? (int) $plain : null;
    }
}
