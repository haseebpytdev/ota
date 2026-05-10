<?php

namespace App\Services\Suppliers;

use App\Contracts\Suppliers\SupplierBookingInterface;
use App\Data\SupplierBookingResultData;
use App\Enums\BookingStatus;
use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierProvider;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\SupplierBooking;
use App\Models\SupplierBookingAttempt;
use App\Models\SupplierConnection;
use App\Models\User;
use App\Services\Booking\BookingOperationalPrecheckService;
use App\Services\Booking\BookingService;
use App\Services\Communication\BookingCommunicationService;
use App\Services\Communication\OtaNotificationService;
use App\Services\Suppliers\BookingAdapters\AirlineDirectSupplierBookingAdapter;
use App\Services\Suppliers\BookingAdapters\DuffelSupplierBookingAdapter;
use App\Services\Suppliers\BookingAdapters\PiaSupplierBookingAdapter;
use App\Services\Suppliers\BookingAdapters\SabreSupplierBookingAdapter;
use App\Support\Security\SensitiveDataRedactor;
use Illuminate\Support\Facades\DB;

class SupplierBookingService
{
    public function __construct(
        protected BookingService $bookingService,
        protected BookingOperationalPrecheckService $operationalPrecheckService,
        protected BookingCommunicationService $communicationService,
        protected OtaNotificationService $otaNotificationService,
        protected SabreSupplierBookingAdapter $sabreAdapter,
        protected PiaSupplierBookingAdapter $piaAdapter,
        protected AirlineDirectSupplierBookingAdapter $airlineDirectAdapter,
        protected DuffelSupplierBookingAdapter $duffelAdapter,
    ) {}

    public function isBookingEligible(Booking $booking, bool $adminOverride = false): bool
    {
        if (($booking->payment_status ?? '') !== 'paid' && ! $adminOverride) {
            return false;
        }

        if (! in_array($booking->status, [BookingStatus::Paid, BookingStatus::PaymentPending, BookingStatus::TicketingPending], true)) {
            return false;
        }

        $meta = $booking->meta ?? [];
        if (($meta['defer_supplier_booking_to_manual_review'] ?? false) === true) {
            return false;
        }
        $hasValidationSnapshot = isset($meta['validated_offer_snapshot']) || isset($meta['normalized_offer_snapshot']);
        $validationStatus = (string) ($meta['offer_validation_status'] ?? '');
        $offerIsValid = in_array($validationStatus, ['valid', 'validated', 'ok', 'pass', 'fresh'], true)
            || ($validationStatus === '' && $hasValidationSnapshot);
        if (! $offerIsValid || ! $hasValidationSnapshot) {
            return false;
        }

        if (((string) ($booking->pnr ?? '')) !== '') {
            return false;
        }

        return $this->operationalPrecheckService->validatePassengerReadiness($booking) === [];
    }

    public function markManualPnr(Booking $booking, User $actor, string $pnr, ?string $supplierReference = null, ?string $note = null): SupplierBooking
    {
        $booking->loadMissing(['latestSupplierBooking']);
        $provider = (string) (($booking->meta['supplier_provider'] ?? null) ?: ($booking->supplier ?? 'manual'));
        $reference = trim((string) ($supplierReference ?? '')) !== '' ? trim((string) $supplierReference) : null;

        return DB::transaction(function () use ($booking, $actor, $pnr, $provider, $reference, $note): SupplierBooking {
            $supplierBooking = SupplierBooking::query()->create([
                'agency_id' => $booking->agency_id,
                'booking_id' => $booking->id,
                'supplier_connection_id' => $booking->latestSupplierBooking?->supplier_connection_id,
                'provider' => $provider !== '' ? $provider : 'manual',
                'supplier_reference' => $reference,
                'pnr' => strtoupper(trim($pnr)),
                'status' => 'pending_ticketing',
                'raw_summary' => SensitiveDataRedactor::redact([
                    'source' => 'manual_pnr',
                    'note' => $note,
                ]),
                'created_by' => $actor->id,
                'created_at_supplier' => now(),
            ]);

            $booking->forceFill([
                'supplier_booking_status' => 'pending_ticketing',
                'supplier_reference' => $reference ?? $booking->supplier_reference,
                'pnr' => strtoupper(trim($pnr)),
                'supplier_booking_created_at' => now(),
            ])->save();

            $this->writeAudit($booking, $actor, 'booking.manual_pnr_marked', [
                'supplier_booking_id' => $supplierBooking->id,
                'provider' => $supplierBooking->provider,
                'supplier_reference' => $supplierBooking->supplier_reference,
                'pnr' => $supplierBooking->pnr,
            ]);

            return $supplierBooking;
        });
    }

    public function createSupplierBooking(Booking $booking, User $actor, bool $adminOverride = false): SupplierBookingResultData
    {
        if (((string) ($booking->pnr ?? '')) !== '') {
            return new SupplierBookingResultData(
                success: false,
                status: 'failed',
                provider: (string) ($booking->supplier ?? 'unknown'),
                error_code: 'pnr_already_exists',
                error_message: 'PNR already exists for this booking.',
            );
        }

        $existing = $booking->supplierBookings()
            ->whereIn('status', ['created', 'pending_ticketing', 'ticketed'])
            ->latest('id')
            ->first();
        if ($existing !== null) {
            return new SupplierBookingResultData(
                success: true,
                status: 'success',
                provider: (string) $existing->provider,
                supplier_reference: $existing->supplier_reference,
                pnr: $existing->pnr,
                safe_summary: (array) ($existing->raw_summary ?? []),
                warnings: ['Supplier booking already exists; returning existing result.'],
            );
        }

        if (! $this->isBookingEligible($booking, $adminOverride)) {
            $precheckErrors = $this->operationalPrecheckService->validatePassengerReadiness($booking);

            return new SupplierBookingResultData(
                success: false,
                status: 'failed',
                provider: (string) ($booking->supplier ?? 'unknown'),
                error_code: 'booking_not_eligible',
                error_message: $precheckErrors !== []
                    ? 'Booking is not eligible for supplier booking: '.$precheckErrors[0]
                    : 'Booking is not eligible for supplier booking.',
                warnings: $precheckErrors,
            );
        }

        $meta = $booking->meta ?? [];
        $provider = (string) ($meta['supplier_provider'] ?? $booking->supplier ?? '');
        $connection = $this->resolveConnection($booking, $provider, $meta['supplier_connection_id'] ?? null);
        if ($connection === null) {
            return new SupplierBookingResultData(
                success: false,
                status: 'failed',
                provider: $provider !== '' ? $provider : 'unknown',
                error_code: 'supplier_connection_missing',
                error_message: 'Supplier connection is not configured.',
            );
        }

        $attempt = SupplierBookingAttempt::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'supplier_connection_id' => $connection->id,
            'provider' => $provider,
            'action' => 'create_pnr',
            'status' => 'processing',
            'attempted_by' => $actor->id,
            'attempted_at' => now(),
        ]);

        $result = $this->resolveAdapter($connection->provider)->createSupplierBooking($booking, $connection, $actor);

        return DB::transaction(function () use ($booking, $actor, $attempt, $connection, $result): SupplierBookingResultData {
            if (! $result->success) {
                $attempt->forceFill([
                    'status' => $result->status === 'not_supported' ? 'failed' : 'failed',
                    'safe_summary' => SensitiveDataRedactor::redact($result->safe_summary),
                    'response_payload' => SensitiveDataRedactor::redact($result->response_payload),
                    'error_code' => $result->error_code,
                    'error_message' => $result->error_message ?: ($result->warnings[0] ?? 'Supplier booking failed.'),
                    'completed_at' => now(),
                ])->save();

                $this->writeAudit($booking, $actor, 'booking.supplier_booking_failed', [
                    'attempt_id' => $attempt->id,
                    'status' => $result->status,
                    'provider' => $result->provider,
                ]);
                $this->otaNotificationService->send(
                    agency: $booking->agency()->firstOrFail(),
                    eventKey: 'supplier_booking_failed',
                    payload: [
                        'booking_reference' => $booking->reference_code,
                        'provider' => $result->provider,
                        'error_code' => $result->error_code,
                        'error_message' => $result->error_message,
                    ],
                    booking: $booking,
                    actor: $actor,
                    fallbackSubject: 'Supplier booking failed',
                    fallbackBody: 'Supplier booking failed for booking '.$booking->reference_code.'.'
                );

                return $result;
            }

            SupplierBooking::query()->create([
                'agency_id' => $booking->agency_id,
                'booking_id' => $booking->id,
                'supplier_connection_id' => $connection->id,
                'provider' => $result->provider,
                'supplier_reference' => $result->supplier_reference,
                'pnr' => $result->pnr,
                'status' => 'pending_ticketing',
                'raw_summary' => SensitiveDataRedactor::redact($result->safe_summary),
                'created_by' => $actor->id,
                'created_at_supplier' => now(),
            ]);

            $attempt->forceFill([
                'status' => 'success',
                'request_payload' => SensitiveDataRedactor::redact($result->request_payload),
                'response_payload' => SensitiveDataRedactor::redact($result->response_payload),
                'safe_summary' => SensitiveDataRedactor::redact($result->safe_summary),
                'supplier_reference' => $result->supplier_reference,
                'completed_at' => now(),
            ])->save();

            $booking->forceFill([
                'supplier_booking_status' => 'pending_ticketing',
                'supplier_reference' => $result->supplier_reference,
                'pnr' => $result->pnr,
                'supplier_booking_created_at' => now(),
            ])->save();

            $this->writeAudit($booking, $actor, 'booking.supplier_booking_created', [
                'attempt_id' => $attempt->id,
                'provider' => $result->provider,
                'supplier_reference' => $result->supplier_reference,
                'pnr' => $result->pnr,
            ]);

            $this->communicationService->sendSupplierBookingCreated($booking->fresh());

            return $result;
        });
    }

    protected function resolveConnection(Booking $booking, string $provider, mixed $connectionId): ?SupplierConnection
    {
        $query = SupplierConnection::query()
            ->where('agency_id', $booking->agency_id)
            ->where(function ($q): void {
                $q->where('is_active', true)->orWhere('status', SupplierConnectionStatus::Active->value);
            });

        if ($connectionId !== null) {
            return $query->where('id', (int) $connectionId)->first();
        }

        return $query->where('provider', $provider)->first();
    }

    protected function resolveAdapter(SupplierProvider $provider): SupplierBookingInterface
    {
        return match ($provider) {
            SupplierProvider::Sabre => $this->sabreAdapter,
            SupplierProvider::Pia => $this->piaAdapter,
            SupplierProvider::AirlineDirect => $this->airlineDirectAdapter,
            SupplierProvider::Duffel => $this->duffelAdapter,
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
