<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WaitlistSlotExpired extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $this->replyTo('events@villasalud.online', 'Villa Salud');

        return $this->subject('Your Reservation Window Has Expired')
            ->view('emails.waitlist_slot_expired')
            ->with(['data' => $this->data]);
    }
}
