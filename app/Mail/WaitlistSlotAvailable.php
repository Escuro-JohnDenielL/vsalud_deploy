<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WaitlistSlotAvailable extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $this->replyTo('coheredit@gmail.com', 'Events Team');

        return $this->subject('A Slot Has Opened Up — Book Now!')
            ->view('emails.waitlist_slot_available')
            ->with(['data' => $this->data]);
    }
}
