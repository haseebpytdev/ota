<?php

namespace App\Mail;

use App\Models\BookingPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BookingPayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment update - '.$this->payment->booking->booking_reference
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.payment-rejected'
        );
    }
}
