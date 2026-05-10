<?php

namespace App\Services\Suppliers;

use App\Models\SupplierConnection;
use App\Models\SupplierDiagnosticLog;
use App\Support\Security\SensitiveDataRedactor;

class SupplierDiagnosticLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(
        SupplierConnection $connection,
        string $action,
        string $status,
        ?int $durationMs = null,
        ?string $safeMessage = null,
        ?string $correlationId = null,
        array $meta = [],
    ): void {
        SupplierDiagnosticLog::query()->create([
            'agency_id' => $connection->agency_id,
            'supplier_connection_id' => $connection->id,
            'provider' => $connection->provider->value,
            'action' => $action,
            'status' => $status,
            'duration_ms' => $durationMs,
            'safe_message' => $safeMessage,
            'correlation_id' => $correlationId,
            'meta' => SensitiveDataRedactor::redact($meta),
        ]);
    }
}
