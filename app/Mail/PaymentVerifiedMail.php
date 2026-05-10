<?php

namespace App\Mail;

use App\Models\BookingPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentVerifiedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BookingPayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment verified - '.$this->payment->booking->booking_reference
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.payment-verified'
        );
    }
}
