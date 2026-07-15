<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationData;
    public $reply;

    /**
     * Create a new message instance.
     */
    public function __construct($reservationData, $reply)
    {
        $this->reservationData = $reservationData;
        $this->reply = $reply;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $this->replyTo('events@send.villasalud.online', 'Villa Salud');

        if ($this->reply) {
            return $this->subject('Reply to your inquiry.')
                ->view('emails.reply')
                ->with([
                    'data' => $this->reply,
                ]);
        }

        return $this->subject('Your Reservation Has Been Submitted')
            ->view('emails.reservation_submitted')
            ->with([
                'data' => $this->reservationData,
            ]);
    }
}
