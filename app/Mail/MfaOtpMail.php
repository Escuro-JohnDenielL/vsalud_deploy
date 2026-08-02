<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MfaOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $this->replyTo('events@villasalud.online', 'Villa Salud');

        return $this->subject('Your Verification Code')
            ->view('emails.mfa_otp')
            ->with(['data' => $this->data]);
    }
}
