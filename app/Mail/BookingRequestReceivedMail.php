<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRequestReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking request received - '.$this->booking->booking_reference
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.request-received'
        );
    }
}
