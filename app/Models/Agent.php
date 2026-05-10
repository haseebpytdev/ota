<?php

namespace App\Models;

use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'agency_id',
    'user_id',
    'code',
    'commission_percent',
    'is_active',
    'meta',
])]
class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'commission_percent' => 'decimal:2',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Agency, $this> */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<AgentCommissionEntry, $this> */
    public function commissionEntries(): HasMany
    {
        return $this->hasMany(AgentCommissionEntry::class);
    }

    /** @return HasMany<AgentCommissionStatement, $this> */
    public function commissionStatements(): HasMany
    {
        return $this->hasMany(AgentCommissionStatement::class);
    }
}
