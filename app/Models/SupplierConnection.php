<?php

namespace App\Models;

use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierEnvironment;
use App\Enums\SupplierProvider;
use Database\Factories\SupplierConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'agency_id',
    'provider',
    'name',
    'environment',
    'status',
    'base_url',
    'display_name',
    'credentials',
    'is_active',
    'last_tested_at',
    'last_test_status',
    'last_error',
    'settings',
    'meta',
])]
#[Hidden(['credentials'])]
class SupplierConnection extends Model
{
    /** @use HasFactory<SupplierConnectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'provider' => SupplierProvider::class,
            'environment' => SupplierEnvironment::class,
            'status' => SupplierConnectionStatus::class,
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
            'settings' => 'array',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Agency, $this> */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
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

    /** @return HasMany<SupplierDiagnosticLog, $this> */
    public function diagnosticLogs(): HasMany
    {
        return $this->hasMany(SupplierDiagnosticLog::class);
    }

    /** @return HasOne<SupplierDiagnosticLog, $this> */
    public function latestReadinessDiagnostic(): HasOne
    {
        return $this->hasOne(SupplierDiagnosticLog::class)
            ->where('action', 'readiness_check')
            ->latest('created_at');
    }

    /** @return HasOne<SupplierDiagnosticLog, $this> */
    public function latestSearchDiagnostic(): HasOne
    {
        return $this->hasOne(SupplierDiagnosticLog::class)
            ->where('action', 'search')
            ->latest('created_at');
    }

    /** @return HasOne<SupplierDiagnosticLog, $this> */
    public function latestOrderDiagnostic(): HasOne
    {
        return $this->hasOne(SupplierDiagnosticLog::class)
            ->where('action', 'create_order')
            ->latest('created_at');
    }

    /**
     * @return array<string, string>
     */
    public function maskedCredentials(): array
    {
        $credentials = $this->credentials ?? [];
        if (! is_array($credentials)) {
            return [];
        }

        $masked = [];
        foreach ($credentials as $key => $value) {
            $text = trim((string) $value);
            if ($text === '') {
                $masked[$key] = '••••••••';

                continue;
            }

            if ($key === 'access_token' && $this->provider === SupplierProvider::Duffel) {
                $prefix = str_starts_with($text, 'duffel_test_') ? 'duffel_test_' : substr($text, 0, min(6, strlen($text)));
                $masked[$key] = $prefix.'••••••••••••';

                continue;
            }

            $tail = strlen($text) > 4 ? substr($text, -4) : $text;
            $masked[$key] = '••••'.$tail;
        }

        return $masked;
    }

    public function isActive(): bool
    {
        return $this->status === SupplierConnectionStatus::Active || $this->is_active;
    }

    public function isLive(): bool
    {
        return $this->environment === SupplierEnvironment::Live;
    }

    public function isSandbox(): bool
    {
        return $this->environment === SupplierEnvironment::Sandbox;
    }
}
