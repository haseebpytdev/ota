<?php

namespace App\Models;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'agency_id',
    'customer_id',
    'agent_id',
    'supplier',
    'route',
    'airline',
    'travel_date',
    'booking_reference',
    'status',
    'cancellation_status',
    'refund_status',
    'payment_status',
    'payment_due_at',
    'amount_paid',
    'balance_due',
    'source_channel',
    'currency',
    'pnr',
    'supplier_booking_status',
    'ticketing_status',
    'ticketed_at',
    'supplier_reference',
    'supplier_booking_created_at',
    'notes',
    'meta',
    'submitted_at',
    'confirmed_at',
    'cancelled_at',
    'assigned_staff_id',
    'assigned_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'meta' => 'array',
            'travel_date' => 'date',
            'submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'assigned_at' => 'datetime',
            'supplier_booking_created_at' => 'datetime',
            'ticketed_at' => 'datetime',
            'payment_due_at' => 'datetime',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Agency, $this> */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /** @return BelongsTo<Agent, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    /** @return HasMany<BookingPassenger, $this> */
    public function passengers(): HasMany
    {
        return $this->hasMany(BookingPassenger::class);
    }

    /** @return HasOne<BookingContact, $this> */
    public function contact(): HasOne
    {
        return $this->hasOne(BookingContact::class);
    }

    /** @return HasOne<BookingFareBreakdown, $this> */
    public function fareBreakdown(): HasOne
    {
        return $this->hasOne(BookingFareBreakdown::class);
    }

    /** @return HasMany<BookingStatusLog, $this> */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(BookingStatusLog::class);
    }

    /** @return HasMany<BookingNote, $this> */
    public function bookingNotes(): HasMany
    {
        return $this->hasMany(BookingNote::class);
    }

    /** @return HasMany<SupplierBookingAttempt, $this> */
    public function supplierBookingAttempts(): HasMany
    {
        return $this->hasMany(SupplierBookingAttempt::class);
    }

    /** @return HasMany<SupplierBooking, $this> */
    public function supplierBookings(): HasMany
    {
        return $this->hasMany(SupplierBooking::class);
    }

    /** @return HasOne<SupplierBooking, $this> */
    public function latestSupplierBooking(): HasOne
    {
        return $this->hasOne(SupplierBooking::class)->latestOfMany();
    }

    /** @return HasMany<BookingPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class);
    }

    /** @return HasMany<BookingPayment, $this> */
    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(BookingPayment::class)->where('status', BookingPaymentStatus::Verified);
    }

    /** @return HasOne<BookingPayment, $this> */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(BookingPayment::class)->latestOfMany();
    }

    /** @return HasMany<BookingTicket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(BookingTicket::class);
    }

    /** @return HasMany<TicketingAttempt, $this> */
    public function ticketingAttempts(): HasMany
    {
        return $this->hasMany(TicketingAttempt::class);
    }

    /** @return HasOne<TicketingAttempt, $this> */
    public function latestTicketingAttempt(): HasOne
    {
        return $this->hasOne(TicketingAttempt::class)->latestOfMany();
    }

    /** @return HasMany<CommunicationLog, $this> */
    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }

    /** @return HasMany<BookingDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(BookingDocument::class);
    }

    /** @return HasMany<GuestBookingAccessToken, $this> */
    public function guestAccessTokens(): HasMany
    {
        return $this->hasMany(GuestBookingAccessToken::class);
    }

    /** @return HasMany<AgentCommissionEntry, $this> */
    public function commissionEntries(): HasMany
    {
        return $this->hasMany(AgentCommissionEntry::class);
    }

    /** @return HasMany<BookingCancellationRequest, $this> */
    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(BookingCancellationRequest::class);
    }

    /** @return HasOne<BookingCancellationRequest, $this> */
    public function latestCancellationRequest(): HasOne
    {
        return $this->hasOne(BookingCancellationRequest::class)->latestOfMany();
    }

    /** @return HasMany<BookingRefund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(BookingRefund::class);
    }
}
