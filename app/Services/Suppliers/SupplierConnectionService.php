<?php

namespace App\Services\Suppliers;

use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierProvider;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\SupplierConnection;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SupplierConnectionService
{
    public function __construct(
        protected SupplierDiagnosticLogger $diagnosticLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeConnection(Agency $agency, array $data): SupplierConnection
    {
        return DB::transaction(function () use ($agency, $data): SupplierConnection {
            $connection = SupplierConnection::query()->create($data + [
                'agency_id' => $agency->id,
            ]);

            $this->writeAudit(
                $connection,
                auth()->user(),
                'supplier.connection_created',
                [],
                $this->auditPayload($connection)
            );

            return $connection->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateConnection(SupplierConnection $connection, array $data): SupplierConnection
    {
        return DB::transaction(function () use ($connection, $data): SupplierConnection {
            $old = $this->auditPayload($connection);
            $credentials = Arr::get($data, 'credentials');
            if (! is_array($credentials) || $credentials === []) {
                unset($data['credentials']);
            }

            $connection->fill($data);
            if ($connection->status === SupplierConnectionStatus::Active) {
                $connection->is_active = true;
            }
            $connection->save();

            $this->writeAudit(
                $connection,
                auth()->user(),
                'supplier.connection_updated',
                $old,
                $this->auditPayload($connection)
            );

            return $connection->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function testConnection(SupplierConnection $connection, User $actor): array
    {
        return DB::transaction(function () use ($connection, $actor): array {
            $credentials = $connection->credentials ?? [];
            $hasCreds = is_array($credentials) && count(array_filter($credentials, fn ($value): bool => trim((string) $value) !== '')) > 0;

            $old = $this->auditPayload($connection);
            $lastError = null;
            $lastTestStatus = null;

            if ($connection->provider === SupplierProvider::Mock) {
                $connection->status = SupplierConnectionStatus::Active;
                $connection->is_active = true;
                $lastTestStatus = 'success';
            } else {
                if ($hasCreds && $this->hasRequiredCredentialKeys($connection->provider, $credentials)) {
                    $lastTestStatus = 'ready_for_review';
                } else {
                    $lastTestStatus = 'missing_credentials';
                    $lastError = 'Required credentials are missing for readiness check.';
                    $connection->status = SupplierConnectionStatus::Error;
                    $connection->is_active = false;
                }
            }

            $connection->last_tested_at = now();
            $connection->last_test_status = $lastTestStatus;
            $connection->last_error = $lastError;
            $connection->save();

            $this->diagnosticLogger->log(
                connection: $connection,
                action: 'readiness_check',
                status: $lastError === null ? 'success' : 'failed',
                safeMessage: $lastError,
                meta: [
                    'last_test_status' => $lastTestStatus,
                ],
            );

            $this->writeAudit(
                $connection,
                $actor,
                'supplier.connection_tested',
                $old,
                $this->auditPayload($connection)
            );

            return [
                'status' => $connection->status->value,
                'last_test_status' => $connection->last_test_status,
                'last_error' => $connection->last_error,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, string>
     */
    public function maskCredentials(array $credentials): array
    {
        $masked = [];
        foreach ($credentials as $key => $value) {
            $text = trim((string) $value);
            $tail = strlen($text) > 4 ? substr($text, -4) : '';
            $masked[$key] = $tail !== '' ? '••••'.$tail : '••••••••';
        }

        return $masked;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function hasRequiredCredentialKeys(SupplierProvider $provider, array $credentials): bool
    {
        $keys = array_map('strtolower', array_keys($credentials));

        return match ($provider) {
            SupplierProvider::Sabre => in_array('client_id', $keys, true) && in_array('client_secret', $keys, true),
            SupplierProvider::Duffel => in_array('access_token', $keys, true),
            SupplierProvider::Pia => in_array('api_key', $keys, true) || (in_array('client_id', $keys, true) && in_array('client_secret', $keys, true)),
            SupplierProvider::AirlineDirect => in_array('api_key', $keys, true) || in_array('token', $keys, true) || (in_array('username', $keys, true) && in_array('password', $keys, true)),
            default => true,
        };
    }

    protected function writeAudit(SupplierConnection $connection, ?User $actor, string $action, array $oldValues, array $newValues): void
    {
        AuditLog::query()->create([
            'agency_id' => $connection->agency_id,
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => SupplierConnection::class,
            'auditable_id' => $connection->id,
            'properties' => [
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditPayload(SupplierConnection $connection): array
    {
        return [
            'provider' => $connection->provider->value,
            'name' => $connection->name,
            'environment' => $connection->environment->value,
            'status' => $connection->status->value,
            'base_url' => $connection->base_url,
            'last_tested_at' => $connection->last_tested_at?->toIso8601String(),
            'last_test_status' => $connection->last_test_status,
            'last_error' => $connection->last_error,
            'credentials' => $this->maskCredentials($connection->credentials ?? []),
        ];
    }
}
