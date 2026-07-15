<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventReminder extends Mailable
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
        $this->replyTo('events@send.villasalud.online', 'Villa Salud');

        return $this->subject('Upcoming Reservation Reminder')
            ->view('emails.event_reminder')
            ->with(['data' => $this->data]);
    }
}
