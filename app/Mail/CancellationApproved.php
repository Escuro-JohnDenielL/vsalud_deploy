<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CancellationApproved extends Mailable
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

        return $this->subject('Your Cancellation Request Has Been Approved')
            ->view('emails.cancellation_approved')
            ->with(['data' => $this->data]);
    }
}
