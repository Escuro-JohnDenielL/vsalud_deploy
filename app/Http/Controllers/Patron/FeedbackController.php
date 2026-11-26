<?php

namespace App\Http\Controllers\Patron;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Inquiry;
use App\Models\Review;
use App\Rules\EmailAddress;
use App\Support\FeedbackInput;
use App\Support\PatronAudit;
use App\Support\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function storeReview(Request $request)
    {
        $request->merge([
            'review_tracking_code' => strtoupper(FeedbackInput::cleanLine((string) $request->input('review_tracking_code', ''))),
            'review_email' => strtolower(FeedbackInput::cleanLine((string) $request->input('review_email', ''))),
            'review_message' => FeedbackInput::cleanMessage((string) $request->input('review_message', '')),
        ]);

        $validated = $request->validate([
            'review_tracking_code' => ['required', 'string', 'max:20'],
            'review_email' => ['required', 'string', 'max:254', new EmailAddress()],
            'review_rating' => ['required', 'integer', 'between:1,5'],
            'review_message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $result = DB::transaction(function () use ($validated) {
            $inquiry = Inquiry::query()
                ->with(['patron', 'reservation'])
                ->where('tracking_code', $validated['review_tracking_code'])
                ->lockForUpdate()
                ->first();

            if (
                ! $inquiry
                || ! $inquiry->patron
                || ! hash_equals(
                    strtolower(trim((string) $inquiry->patron->email)),
                    $validated['review_email']
                )
                || ! $inquiry->reservation
                || $inquiry->reservation->status !== 'completed'
            ) {
                return ['status' => 'ineligible'];
            }

            if ($inquiry->review()->exists()) {
                return ['status' => 'duplicate'];
            }

            Review::create([
                'inquiry_id' => $inquiry->inquiry_id,
                'display_name' => 'Verified patron',
                'rating' => $validated['review_rating'],
                'message' => $validated['review_message'],
            ]);

            return ['status' => 'created', 'inquiry' => $inquiry];
        });

        if ($result['status'] === 'ineligible') {
            return redirect()->back()->withErrors([
                'review_tracking_code' => 'We could not verify an eligible completed booking. Check the code and booking email, then try again.',
            ])->withInput($request->except(['review_tracking_code', 'review_email']));
        }

        if ($result['status'] === 'duplicate') {
            return redirect()->back()->withErrors([
                'review_tracking_code' => 'A review has already been submitted for this booking.',
            ]);
        }

        /** @var Inquiry $inquiry */
        $inquiry = $result['inquiry'];
        PatronAudit::log(
            'Patron Review Submitted',
            $request,
            $inquiry->tracking_code,
            'verified review submitted',
            $inquiry->inquiry_id
        );

        return redirect()->back()->with('success', 'Thank you for sharing your verified review!');
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
