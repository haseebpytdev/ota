<?php

namespace App\Services\Bookings;

use App\Enums\BookingCancellationStatus;
use App\Enums\BookingCancellationType;
use App\Enums\BookingStatus;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingCancellationRequest;
use App\Models\CommunicationLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingCancellationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function requestCancellation(Booking $booking, ?User $actor, array $data): BookingCancellationRequest
    {
        return DB::transaction(function () use ($booking, $actor, $data): BookingCancellationRequest {
            $request = BookingCancellationRequest::query()->create([
                'agency_id' => $booking->agency_id,
                'booking_id' => $booking->id,
                'requested_by' => $actor?->id,
                'request_source' => (string) ($data['request_source'] ?? ($actor === null ? 'guest' : $actor->account_type?->value ?? 'user')),
                'reason' => $data['reason'] ?? null,
                'status' => BookingCancellationStatus::Requested,
                'cancellation_type' => $data['cancellation_type'] ?? BookingCancellationType::BookingCancel->value,
                'meta' => $data['meta'] ?? null,
            ]);

            $booking->forceFill(['cancellation_status' => BookingCancellationStatus::Requested->value])->save();

            $this->writeAudit($booking, $actor, 'booking.cancellation_requested', [
                'cancellation_request_id' => $request->id,
                'request_source' => $request->request_source,
            ]);

            $this->writeCommunication($booking, $actor, 'cancellation_requested', [
                'cancellation_request_id' => $request->id,
                'request_source' => $request->request_source,
            ]);

            return $request;
        });
    }

    public function approveCancellation(BookingCancellationRequest $request, User $actor): BookingCancellationRequest
    {
        return DB::transaction(function () use ($request, $actor): BookingCancellationRequest {
            if ($request->status !== BookingCancellationStatus::Requested) {
                throw new InvalidArgumentException('Only requested cancellations can be approved.');
            }

            $request->forceFill([
                'status' => BookingCancellationStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ])->save();

            $request->booking->forceFill(['cancellation_status' => BookingCancellationStatus::Approved->value])->save();

            $this->writeAudit($request->booking, $actor, 'booking.cancellation_approved', [
                'cancellation_request_id' => $request->id,
            ]);

            return $request->fresh();
        });
    }

    public function rejectCancellation(BookingCancellationRequest $request, User $actor, string $reason): BookingCancellationRequest
    {
        return DB::transaction(function () use ($request, $actor, $reason): BookingCancellationRequest {
            if (! in_array($request->status, [BookingCancellationStatus::Requested, BookingCancellationStatus::Approved], true)) {
                throw new InvalidArgumentException('Only requested/approved cancellations can be rejected.');
            }

            $request->forceFill([
                'status' => BookingCancellationStatus::Rejected,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $request->booking->forceFill(['cancellation_status' => BookingCancellationStatus::Rejected->value])->save();

            $this->writeAudit($request->booking, $actor, 'booking.cancellation_rejected', [
                'cancellation_request_id' => $request->id,
                'reason' => $reason,
            ]);

            return $request->fresh();
        });
    }

    public function processCancellation(BookingCancellationRequest $request, User $actor): BookingCancellationRequest
    {
        return DB::transaction(function () use ($request, $actor): BookingCancellationRequest {
            if (! in_array($request->status, [BookingCancellationStatus::Approved, BookingCancellationStatus::Requested], true)) {
                throw new InvalidArgumentException('Only requested/approved cancellations can be processed.');
            }

            $booking = $request->booking()->lockForUpdate()->firstOrFail();
            $isTicketed = $booking->tickets()->exists() || $booking->status === BookingStatus::Ticketed;

            $requestMeta = $request->meta ?? [];
            $bookingMeta = $booking->meta ?? [];

            if (! $isTicketed) {
                $from = $booking->status;
                $booking->forceFill([
                    'status' => BookingStatus::Cancelled,
                    'cancellation_status' => BookingCancellationStatus::Processed->value,
                    'cancelled_at' => now(),
                ])->save();

                $booking->statusLogs()->create([
                    'from_status' => $from?->value,
                    'to_status' => BookingStatus::Cancelled->value,
                    'user_id' => $actor->id,
                    'note' => 'Booking cancelled via cancellation workflow',
                    'context' => ['cancellation_request_id' => $request->id],
                ]);

                $this->writeCommunication($booking, $actor, 'booking_cancelled', [
                    'cancellation_request_id' => $request->id,
                ]);
            } else {
                $warning = 'Ticketed booking requires manual supplier void/refund handling until supplier API docs are reviewed.';
                $requestMeta['manual_warning'] = $warning;
                $bookingMeta['manual_void_refund_warning'] = $warning;
                $bookingMeta['manual_void_refund_review_required'] = true;
                $booking->forceFill([
                    'cancellation_status' => BookingCancellationStatus::Processed->value,
                    'meta' => $bookingMeta,
                ])->save();
            }

            $request->forceFill([
                'status' => BookingCancellationStatus::Processed,
                'processed_by' => $actor->id,
                'processed_at' => now(),
                'meta' => $requestMeta,
            ])->save();

            $this->writeAudit($booking, $actor, 'booking.cancellation_processed', [
                'cancellation_request_id' => $request->id,
                'ticketed' => $isTicketed,
            ]);

            return $request->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $newValues
     */
    protected function writeAudit(Booking $booking, ?User $actor, string $action, array $newValues): void
    {
        AuditLog::query()->create([
            'agency_id' => $booking->agency_id,
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
            'properties' => [
                'old_values' => [],
                'new_values' => $newValues,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function writeCommunication(Booking $booking, ?User $actor, string $event, array $meta = []): void
    {
        CommunicationLog::query()->create([
            'agency_id' => $booking->agency_id,
            'booking_id' => $booking->id,
            'user_id' => $booking->customer_id,
            'channel' => 'system',
            'event' => $event,
            'recipient_name' => $booking->contact?->name ?? $booking->customer?->name,
            'recipient_email' => $booking->contact?->email ?? $booking->customer?->email,
            'status' => 'logged',
            'meta' => array_merge($meta, [
                'actor_id' => $actor?->id,
            ]),
            'sent_at' => now(),
        ]);
    }
}
