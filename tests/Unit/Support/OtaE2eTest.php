<?php

namespace Tests\Unit\Support;

use App\Support\OtaE2e;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtaE2eTest extends TestCase
{
    #[Test]
    public function force_mock_is_false_outside_local_and_testing_even_when_config_true(): void
    {
        $this->assertFalse(OtaE2e::shouldForceMockSupplierInEnvironment('production', true));
        $this->assertFalse(OtaE2e::shouldForceMockSupplierInEnvironment('staging', true));
    }

    #[Test]
    public function force_mock_requires_config_flag_in_safe_environments(): void
    {
        $this->assertFalse(OtaE2e::shouldForceMockSupplierInEnvironment('local', false));
        $this->assertFalse(OtaE2e::shouldForceMockSupplierInEnvironment('testing', false));
        $this->assertTrue(OtaE2e::shouldForceMockSupplierInEnvironment('local', true));
        $this->assertTrue(OtaE2e::shouldForceMockSupplierInEnvironment('testing', true));
    }
}
