<?php

namespace App\Services\Suppliers;

use App\Contracts\Suppliers\SupplierTicketingInterface;
use App\Data\TicketingResultData;
use App\Enums\BookingStatus;
use App\Enums\SupplierProvider;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\BookingTicket;
use App\Models\SupplierBooking;
use App\Models\TicketingAttempt;
use App\Models\User;
use App\Services\Agents\AgentCommissionService;
use App\Services\Booking\BookingOperationalPrecheckService;
use App\Services\Communication\BookingCommunicationService;
use App\Services\Communication\OtaNotificationService;
use App\Services\Suppliers\TicketingAdapters\AirlineDirectSupplierTicketingAdapter;
use App\Services\Suppliers\TicketingAdapters\PiaSupplierTicketingAdapter;
use App\Services\Suppliers\TicketingAdapters\SabreSupplierTicketingAdapter;
use App\Support\Security\SensitiveDataRedactor;
use Illuminate\Support\Facades\DB;

class TicketingService
{
    public function __construct(
        protected AgentCommissionService $agentCommissionService,
        protected BookingOperationalPrecheckService $operationalPrecheckService,
        protected BookingCommunicationService $communicationService,
        protected OtaNotificationService $otaNotificationService,
        protected SabreSupplierTicketingAdapter $sabreAdapter,
        protected PiaSupplierTicketingAdapter $piaAdapter,
        protected AirlineDirectSupplierTicketingAdapter $airlineDirectAdapter,
    ) {}

    public function isBookingEligibleForTicketing(Booking $booking): bool
    {
        if ($booking->status === BookingStatus::Ticketed || ($booking->ticketing_status ?? '') === 'ticketed') {
            return false;
        }

        if (($booking->payment_status ?? '') !== 'paid') {
            return false;
        }

        if (! in_array($booking->status, [BookingStatus::Paid, BookingStatus::TicketingPending], true)) {
            return false;
        }

        $supplierBooking = $booking->latestSupplierBooking;
        if ($supplierBooking === null) {
            return false;
        }

        if (! in_array($supplierBooking->status, ['pending_ticketing', 'created'], true)) {
            return false;
        }

        if (! (($booking->pnr ?? '') !== '' || ($booking->supplier_reference ?? '') !== '')) {
            return false;
        }

        return $this->operationalPrecheckService->validatePassengerReadiness($booking) === [];
    }

    public function issueTickets(Booking $booking, User $actor): TicketingResultData
    {
        $booking->loadMissing(['passengers', 'latestSupplierBooking']);

        if (! $this->isBookingEligibleForTicketing($booking)) {
            $precheckErrors = $this->operationalPrecheckService->validatePassengerReadiness($booking);

            return new TicketingResultData(
                success: false,
                status: 'failed',
                provider: (string) ($booking->supplier ?? 'unknown'),
                error_code: 'booking_not_eligible',
                error_message: $precheckErrors !== []
                    ? 'Booking is not eligible for ticketing: '.$precheckErrors[0]
                    : 'Booking is not eligible for ticketing.',
                warnings: $precheckErrors,
            );
        }

        $supplierBooking = $booking->latestSupplierBooking;
        if ($supplierBooking === null) {
            return new TicketingResultData(
                success: false,
                status: 'failed',
                provider: (string) ($booking->supplier ?? 'unknown'),
                error_code: 'supplier_booking_missing',
                error_message: 'Supplier booking is required before ticketing.',
            );
        }

        $attempt = TicketingAttempt::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'supplier_booking_id' => $supplierBooking->id,
            'provider' => $supplierBooking->provider,
            'status' => 'processing',
            'attempted_by' => $actor->id,
            'attempted_at' => now(),
        ]);

        $result = $this->resolveAdapter($supplierBooking)->issueTickets($booking, $supplierBooking, $actor);

        return DB::transaction(function () use ($booking, $supplierBooking, $attempt, $result, $actor): TicketingResultData {
            if (! $result->success) {
                $attempt->forceFill([
                    'status' => $result->status === 'not_supported' ? 'not_supported' : 'failed',
                    'request_payload' => SensitiveDataRedactor::redact($result->request_payload),
                    'response_payload' => SensitiveDataRedactor::redact($result->response_payload),
                    'safe_summary' => SensitiveDataRedactor::redact($result->safe_summary),
                    'error_code' => $result->error_code,
                    'error_message' => $result->error_message ?: ($result->warnings[0] ?? 'Ticketing failed.'),
                    'completed_at' => now(),
                ])->save();

                $this->writeAudit($booking, $actor, 'booking.ticketing_failed', [
                    'attempt_id' => $attempt->id,
                    'provider' => $result->provider,
                    'status' => $result->status,
                ]);
                $this->otaNotificationService->send(
                    agency: $booking->agency()->firstOrFail(),
                    eventKey: $result->status === 'not_supported' ? 'ticketing_not_supported' : 'ticketing_failed',
                    payload: [
                        'booking_reference' => $booking->reference_code,
                        'provider' => $result->provider,
                        'error_code' => $result->error_code,
                        'error_message' => $result->error_message,
                    ],
                    booking: $booking,
                    actor: $actor,
                    fallbackSubject: 'Ticketing issue detected',
                    fallbackBody: 'Ticketing failed for booking '.$booking->reference_code.'.'
                );

                return $result;
            }

            foreach ($result->tickets as $ticketData) {
                $ticket = BookingTicket::query()->create([
                    'agency_id' => $booking->agency_id,
                    'booking_id' => $booking->id,
                    'supplier_booking_id' => $supplierBooking->id,
                    'passenger_id' => $ticketData['passenger_id'] ?? null,
                    'ticket_number' => $ticketData['ticket_number'] ?? null,
                    'pnr' => $ticketData['pnr'] ?? ($booking->pnr ?? null),
                    'provider' => $result->provider,
                    'airline_code' => $ticketData['airline_code'] ?? null,
                    'status' => 'issued',
                    'issued_by' => $actor->id,
                    'issued_at' => isset($ticketData['issued_at']) ? $ticketData['issued_at'] : now(),
                    'raw_summary' => $ticketData,
                    'meta' => SensitiveDataRedactor::redact(['passenger_name' => $ticketData['passenger_name'] ?? null]),
                ]);

                if ($booking->agent_id !== null) {
                    $this->agentCommissionService->generateCommissionForTicket($ticket);
                }
            }

            $attempt->forceFill([
                'status' => 'success',
                'request_payload' => SensitiveDataRedactor::redact($result->request_payload),
                'response_payload' => SensitiveDataRedactor::redact($result->response_payload),
                'safe_summary' => SensitiveDataRedactor::redact($result->safe_summary),
                'completed_at' => now(),
            ])->save();

            $previousStatus = $booking->status;
            $booking->forceFill([
                'status' => BookingStatus::Ticketed,
                'ticketing_status' => 'ticketed',
                'ticketed_at' => now(),
            ])->save();

            $supplierBooking->forceFill(['status' => 'ticketed'])->save();

            BookingStatusLog::query()->create([
                'booking_id' => $booking->id,
                'from_status' => $previousStatus->value,
                'to_status' => BookingStatus::Ticketed->value,
                'user_id' => $actor->id,
                'note' => 'Tickets issued',
                'context' => [
                    'source' => 'ticketing_service',
                    'supplier_booking_id' => $supplierBooking->id,
                ],
            ]);

            $this->writeAudit($booking, $actor, 'booking.tickets_issued', [
                'attempt_id' => $attempt->id,
                'provider' => $result->provider,
                'tickets_count' => count($result->tickets),
            ]);

            $this->communicationService->sendTicketIssued($booking->fresh());

            return $result;
        });
    }

    protected function resolveAdapter(SupplierBooking $supplierBooking): SupplierTicketingInterface
    {
        $provider = SupplierProvider::tryFrom($supplierBooking->provider);

        return match ($provider) {
            SupplierProvider::Sabre => $this->sabreAdapter,
            SupplierProvider::Pia => $this->piaAdapter,
            SupplierProvider::AirlineDirect => $this->airlineDirectAdapter,
            default => $this->piaAdapter,
        };
    }

    /**
     * @param  array<string, mixed>  $newValues
     */
    protected function writeAudit(Booking $booking, User $actor, string $action, array $newValues): void
    {
        AuditLog::query()->create([
            'agency_id' => $booking->agency_id,
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
            'properties' => [
                'old_values' => [],
                'new_values' => $newValues,
            ],
        ]);
    }
}
