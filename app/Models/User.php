<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AccountType;
use App\Enums\UserAccountStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'current_agency_id', 'account_type', 'status', 'invited_at', 'last_login_at', 'meta'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'account_type' => AccountType::class,
            'status' => UserAccountStatus::class,
            'invited_at' => 'datetime',
            'last_login_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Agency, $this> */
    public function currentAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'current_agency_id');
    }

    /** @return BelongsToMany<Agency, $this, AgencyUser> */
    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class, 'agency_users')
            ->using(AgencyUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    /** @return HasMany<StaffProfile, $this> */
    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }

    /** @return HasOne<StaffProfile, $this> */
    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    /** @return HasMany<Agent, $this> */
    public function agentProfiles(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    /** @return HasOne<Agent, $this> */
    public function agentProfile(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function agent(): ?Agent
    {
        $agencyId = $this->current_agency_id;
        if ($agencyId === null) {
            return null;
        }

        return $this->agentProfiles()
            ->where('agency_id', $agencyId)
            ->first();
    }

    public function isPlatformAdmin(): bool
    {
        return $this->account_type === AccountType::PlatformAdmin;
    }

    public function isAgencyAdmin(): bool
    {
        return $this->account_type === AccountType::AgencyAdmin;
    }

    public function isStaff(): bool
    {
        return $this->account_type === AccountType::Staff;
    }

    public function isAgent(): bool
    {
        return $this->account_type === AccountType::Agent;
    }

    public function isCustomer(): bool
    {
        return $this->account_type === AccountType::Customer;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserAccountStatus::Suspended;
    }

    public function belongsToAgency(int $agencyId): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        return $this->agencies()->where('agencies.id', $agencyId)->exists();
    }

    /** @return HasMany<BookingNote, $this> */
    public function bookingNotes(): HasMany
    {
        return $this->hasMany(BookingNote::class);
    }

    /** @return HasMany<BookingPayment, $this> */
    public function submittedPayments(): HasMany
    {
        return $this->hasMany(BookingPayment::class, 'payer_user_id');
    }

    /** @return HasMany<BookingPayment, $this> */
    public function receivedPayments(): HasMany
    {
        return $this->hasMany(BookingPayment::class, 'received_by');
    }

    /** @return HasMany<BookingTicket, $this> */
    public function issuedTickets(): HasMany
    {
        return $this->hasMany(BookingTicket::class, 'issued_by');
    }

    /** @return HasMany<TicketingAttempt, $this> */
    public function ticketingAttempts(): HasMany
    {
        return $this->hasMany(TicketingAttempt::class, 'attempted_by');
    }

    /** @return HasMany<CommunicationLog, $this> */
    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }

    /** @return HasMany<BookingDocument, $this> */
    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(BookingDocument::class, 'generated_by');
    }

    /** @return HasMany<AgentCommissionEntry, $this> */
    public function approvedCommissionEntries(): HasMany
    {
        return $this->hasMany(AgentCommissionEntry::class, 'approved_by');
    }

    /** @return HasMany<AgentCommissionEntry, $this> */
    public function paidCommissionEntries(): HasMany
    {
        return $this->hasMany(AgentCommissionEntry::class, 'paid_by');
    }

    /** @return HasMany<AgentCommissionStatement, $this> */
    public function issuedCommissionStatements(): HasMany
    {
        return $this->hasMany(AgentCommissionStatement::class, 'issued_by');
    }

    /** @return HasMany<BookingCancellationRequest, $this> */
    public function requestedCancellations(): HasMany
    {
        return $this->hasMany(BookingCancellationRequest::class, 'requested_by');
    }

    /** @return HasMany<BookingCancellationRequest, $this> */
    public function approvedCancellations(): HasMany
    {
        return $this->hasMany(BookingCancellationRequest::class, 'approved_by');
    }

    /** @return HasMany<BookingCancellationRequest, $this> */
    public function processedCancellations(): HasMany
    {
        return $this->hasMany(BookingCancellationRequest::class, 'processed_by');
    }

    /** @return HasMany<BookingRefund, $this> */
    public function approvedRefunds(): HasMany
    {
        return $this->hasMany(BookingRefund::class, 'approved_by');
    }

    /** @return HasMany<BookingRefund, $this> */
    public function paidRefunds(): HasMany
    {
        return $this->hasMany(BookingRefund::class, 'paid_by');
    }

    /** @return HasMany<AgencyMedia, $this> */
    public function uploadedAgencyMedia(): HasMany
    {
        return $this->hasMany(AgencyMedia::class, 'uploaded_by');
    }
}
