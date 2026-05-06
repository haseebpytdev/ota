<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $statusLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking status update - '.$this->booking->booking_reference
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.status-changed'
        );
    }
}
