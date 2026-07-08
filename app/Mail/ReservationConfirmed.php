<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationData;

    /**
     * Create a new message instance.
     */
    public function __construct($reservationData)
    {
        $this->reservationData = $reservationData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $this->replyTo('coheredit@gmail.com', 'Events Team');

        return $this->subject('Your Reservation Has Been Confirmed')
            ->view('emails.reservation_confirmed')
            ->with([
                'data' => $this->reservationData,
            ]);
    }
}
