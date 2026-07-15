<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReminder extends Mailable
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

        return $this->subject('Payment Reminder for Your Reservation')
            ->view('emails.payment_reminder')
            ->with(['data' => $this->data]);
    }
}
