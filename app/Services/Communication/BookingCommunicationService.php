<?php

namespace App\Services\Communication;

use App\Enums\BookingCommunicationEvent;
use App\Enums\OtaNotificationEvent;
use App\Mail\BookingRequestReceivedMail;
use App\Mail\BookingStatusChangedMail;
use App\Mail\PaymentRejectedMail;
use App\Mail\PaymentVerifiedMail;
use App\Mail\TicketIssuedMail;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\CommunicationLog;
use App\Models\User;
use App\Services\Customer\GuestBookingAccessService;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BookingCommunicationService
{
    public function __construct(
        protected GuestBookingAccessService $guestAccessService,
        protected AgencyCommunicationSettingsService $agencyCommunicationSettingsService,
        protected OtaNotificationService $otaNotificationService,
    ) {}

    public function sendBookingRequestReceived(Booking $booking): void
    {
        $booking = $booking->fresh(['agency.agencySetting', 'contact', 'passengers', 'customer']);
        $guestToken = $this->guestAccessService->createTokenForBooking(
            $booking,
            $booking->contact?->email,
            $booking->contact?->phone
        );

        $this->sendEmailForBooking(
            $booking,
            BookingCommunicationEvent::BookingRequestReceived,
            fn (Booking $b): Mailable => new BookingRequestReceivedMail($b),
            [
                'guest_lookup_token_created' => true,
                'guest_lookup_expires_at' => now()->addMinutes((int) config('ota.guest_lookup_token_minutes', 30))->toIso8601String(),
                'token_not_emailed' => true,
                'token_length' => strlen($guestToken),
            ]
        );
        $this->notifyAdmin($booking, OtaNotificationEvent::BookingRequestReceived, [
            'booking_reference' => $booking->reference_code,
            'route' => $booking->route,
            'travel_date' => optional($booking->travel_date)->toDateString(),
            'passenger_count' => $booking->passengers()->count(),
        ]);
    }

    public function sendBookingConfirmed(Booking $booking): void
    {
        $booking = $booking->fresh(['agency.agencySetting', 'contact', 'customer']);
        $this->sendEmailForBooking(
            $booking,
            BookingCommunicationEvent::BookingConfirmed,
            fn (Booking $b): Mailable => new BookingStatusChangedMail($b, 'confirmed')
        );
        $this->notifyAdmin($booking, OtaNotificationEvent::BookingConfirmed, [
            'booking_reference' => $booking->reference_code,
        ]);
    }

    public function sendPaymentSubmitted(BookingPayment $payment): void
    {
        $booking = $payment->booking()->firstOrFail();
        $this->logSystemEvent($booking, BookingCommunicationEvent::PaymentSubmitted->value, [
            'payment_id' => $payment->id,
            'amount' => (float) $payment->amount,
        ]);
        $this->notifyAdmin($booking, OtaNotificationEvent::PaymentProofSubmitted, [
            'booking_reference' => $booking->reference_code,
            'payment_id' => $payment->id,
            'amount' => (float) $payment->amount,
        ]);
    }

    public function sendPaymentVerified(BookingPayment $payment): void
    {
        $payment = $payment->fresh(['booking.agency.agencySetting', 'booking.contact', 'booking.customer']);
        $this->sendEmailForBooking(
            $payment->booking,
            BookingCommunicationEvent::PaymentVerified,
            fn (): Mailable => new PaymentVerifiedMail($payment)
        );
        $this->notifyAdmin($payment->booking, OtaNotificationEvent::PaymentVerified, [
            'booking_reference' => $payment->booking->reference_code,
            'amount' => (float) $payment->amount,
        ]);
    }

    public function sendPaymentRejected(BookingPayment $payment): void
    {
        $payment = $payment->fresh(['booking.agency.agencySetting', 'booking.contact', 'booking.customer']);
        $this->sendEmailForBooking(
            $payment->booking,
            BookingCommunicationEvent::PaymentRejected,
            fn (): Mailable => new PaymentRejectedMail($payment)
        );
        $this->notifyAdmin($payment->booking, OtaNotificationEvent::PaymentRejected, [
            'booking_reference' => $payment->booking->reference_code,
            'amount' => (float) $payment->amount,
        ]);
    }

    public function sendSupplierBookingCreated(Booking $booking): void
    {
        $this->logSystemEvent($booking, BookingCommunicationEvent::SupplierBookingCreated->value, [
            'supplier_booking_status' => $booking->supplier_booking_status,
            'pnr' => $booking->pnr,
        ]);
        $this->notifyAdmin($booking, OtaNotificationEvent::SupplierBookingCreated, [
            'booking_reference' => $booking->reference_code,
            'supplier_booking_status' => $booking->supplier_booking_status,
            'pnr' => $booking->pnr,
        ]);
    }

    public function sendTicketIssued(Booking $booking): void
    {
        $booking = $booking->fresh(['agency.agencySetting', 'contact', 'customer', 'tickets']);
        $this->sendEmailForBooking(
            $booking,
            BookingCommunicationEvent::TicketIssued,
            fn (Booking $b): Mailable => new TicketIssuedMail($b)
        );
        $this->notifyAdmin($booking, OtaNotificationEvent::TicketIssued, [
            'booking_reference' => $booking->reference_code,
            'tickets_count' => $booking->tickets()->count(),
        ]);
    }

    public function logSystemEvent(Booking $booking, string $event, array $meta = []): CommunicationLog
    {
        return CommunicationLog::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'user_id' => $booking->customer_id,
            'channel' => 'system',
            'event' => $event,
            'status' => 'sent',
            'meta' => $meta,
            'sent_at' => now(),
        ]);
    }

    public function sendBookingStatusChanged(Booking $booking, string $statusLabel): void
    {
        $booking = $booking->fresh(['agency.agencySetting', 'contact', 'customer']);
        $event = ($booking->status->value === 'cancelled')
            ? BookingCommunicationEvent::BookingCancelled
            : BookingCommunicationEvent::BookingStatusChanged;

        $this->sendEmailForBooking(
            $booking,
            $event,
            fn (Booking $b): Mailable => new BookingStatusChangedMail($b, $statusLabel),
            ['status_label' => $statusLabel]
        );
        $this->notifyAdmin($booking, OtaNotificationEvent::BookingStatusChanged, [
            'booking_reference' => $booking->reference_code,
            'status_label' => $statusLabel,
        ]);
    }

    public function sendStaffAssigned(Booking $booking, ?User $assignee): void
    {
        $this->logSystemEvent($booking, BookingCommunicationEvent::StaffAssigned->value, [
            'assigned_staff_id' => $assignee?->id,
            'assigned_staff_name' => $assignee?->name,
        ]);
        $this->notifyAdmin($booking, OtaNotificationEvent::BookingAssigned, [
            'booking_reference' => $booking->reference_code,
            'assigned_staff_id' => $assignee?->id,
            'assigned_staff_name' => $assignee?->name,
        ]);
    }

    /**
     * @param  callable(Booking): Mailable  $mailableFactory
     * @param  array<string, mixed>  $meta
     */
    protected function sendEmailForBooking(
        Booking $booking,
        BookingCommunicationEvent $event,
        callable $mailableFactory,
        array $meta = [],
    ): void {
        $settings = $this->agencyCommunicationSettingsService->getOrCreateSettings($booking->agency);
        $recipient = $this->resolveRecipient($booking);
        $renderedTemplate = $this->agencyCommunicationSettingsService->renderTemplate(
            $booking->agency,
            $event->value,
            'email',
            [
                'agency_name' => (string) ($booking->agency?->agencySetting?->display_name ?? $booking->agency?->name ?? config('app.name')),
                'booking_reference' => (string) $booking->reference_code,
                'passenger_name' => (string) ($recipient['name'] ?? 'Passenger'),
            ]
        );
        $log = CommunicationLog::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'user_id' => $recipient['user_id'],
            'channel' => 'email',
            'event' => $event->value,
            'recipient_name' => $recipient['name'],
            'recipient_email' => $recipient['email'],
            'recipient_phone' => $recipient['phone'],
            'status' => 'queued',
            'provider' => config('mail.default'),
            'meta' => array_merge($meta, ['used_template' => $renderedTemplate['used_template']]),
        ]);

        if (! $settings->email_enabled) {
            $log->forceFill([
                'status' => 'skipped',
                'error_message' => 'Email notifications are disabled for this agency.',
            ])->save();
            $this->logWhatsappFutureProviderEvent($booking, $event->value, $settings->notification_rules ?? []);

            return;
        }

        if ($renderedTemplate['used_template'] && ! $renderedTemplate['is_enabled']) {
            $log->forceFill([
                'status' => 'skipped',
                'error_message' => 'Email template is disabled for this event.',
            ])->save();
            $this->logWhatsappFutureProviderEvent($booking, $event->value, $settings->notification_rules ?? []);

            return;
        }

        if ($recipient['email'] === null) {
            $log->forceFill([
                'status' => 'skipped',
                'error_message' => 'Recipient email is missing.',
            ])->save();
            $this->logWhatsappFutureProviderEvent($booking, $event->value, $settings->notification_rules ?? []);

            return;
        }

        try {
            $mailable = $mailableFactory($booking);
            $subject = $mailable->envelope()->subject;

            if ($this->isImmediateMailer()) {
                Mail::to($recipient['email'])->send($mailable);
                $log->forceFill([
                    'status' => 'sent',
                    'subject' => $renderedTemplate['subject'] ?: $subject,
                    'message' => $renderedTemplate['body'] ?: null,
                    'sent_at' => now(),
                ])->save();
                $this->logWhatsappFutureProviderEvent($booking, $event->value, $settings->notification_rules ?? []);

                return;
            }

            Mail::to($recipient['email'])->queue($mailable);
            $log->forceFill([
                'status' => 'queued',
                'subject' => $renderedTemplate['subject'] ?: $subject,
                'message' => $renderedTemplate['body'] ?: null,
            ])->save();
            $this->logWhatsappFutureProviderEvent($booking, $event->value, $settings->notification_rules ?? []);
        } catch (Throwable $e) {
            $log->forceFill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ])->save();
            $this->logWhatsappFutureProviderEvent($booking, $event->value, $settings->notification_rules ?? []);
        }
    }

    protected function logWhatsappFutureProviderEvent(Booking $booking, string $event, array $rules): void
    {
        if (! (($rules['whatsapp'] ?? false) === true)) {
            return;
        }

        CommunicationLog::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'user_id' => $booking->customer_id,
            'channel' => 'whatsapp',
            'event' => $event,
            'status' => 'queued_for_future_provider',
            'provider' => 'not_configured_yet',
            'meta' => ['note' => 'WhatsApp sending is not enabled yet by product design.'],
        ]);
    }

    /**
     * @return array{name: string|null, email: string|null, phone: string|null, user_id: int|null}
     */
    protected function resolveRecipient(Booking $booking): array
    {
        $booking->loadMissing(['contact', 'customer']);

        return [
            'name' => $booking->contact?->meta['name']
                ?? $booking->customer?->name
                ?? trim((string) optional($booking->passengers->first())->first_name.' '.optional($booking->passengers->first())->last_name)
                ?: null,
            'email' => $booking->contact?->email ?? $booking->customer?->email,
            'phone' => $booking->contact?->phone,
            'user_id' => $booking->customer_id,
        ];
    }

    protected function isImmediateMailer(): bool
    {
        return in_array((string) config('mail.default'), ['log', 'array', 'local'], true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function notifyAdmin(Booking $booking, OtaNotificationEvent $event, array $payload): void
    {
        $this->otaNotificationService->send(
            agency: $booking->agency()->firstOrFail(),
            eventKey: $event->value,
            booking: $booking,
            actor: $booking->customer,
            payload: $payload,
            fallbackSubject: 'OTA Notification: '.str_replace('_', ' ', $event->value),
            fallbackBody: 'A new '.$event->value.' event was recorded for booking '.$booking->reference_code.'.'
        );
    }
}
