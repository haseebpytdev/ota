<?php

namespace App\Support;

/**
 * Local/testing automation helpers — never enables supplier shortcuts outside safe environments.
 */
final class OtaE2e
{
    /**
     * Force deterministic mock-supplier flight search + validation (Playwright / local QA).
     * Ignored in production and staging regardless of env vars.
     */
    public static function shouldForceMockSupplier(): bool
    {
        return self::shouldForceMockSupplierInEnvironment(app()->environment(), self::rawForceMockConfig());
    }

    /**
     * @internal Used by tests to assert environment gating without bootstrapping multiple apps.
     */
    public static function shouldForceMockSupplierInEnvironment(string $environment, bool $configFlag): bool
    {
        if (! in_array($environment, ['local', 'testing'], true)) {
            return false;
        }

        return $configFlag;
    }

    public static function rawForceMockConfig(): bool
    {
        return filter_var(config('ota.e2e_force_mock_supplier'), FILTER_VALIDATE_BOOL);
    }
}
