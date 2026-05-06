<?php

namespace Database\Factories;

use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierEnvironment;
use App\Enums\SupplierProvider;
use App\Models\Agency;
use App\Models\SupplierConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierConnection>
 */
class SupplierConnectionFactory extends Factory
{
    protected $model = SupplierConnection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'provider' => SupplierProvider::Mock,
            'name' => 'Mock supplier',
            'environment' => SupplierEnvironment::Demo,
            'status' => SupplierConnectionStatus::Active,
            'base_url' => null,
            'display_name' => 'Mock supplier',
            'credentials' => null,
            'is_active' => true,
            'last_tested_at' => null,
            'last_test_status' => null,
            'last_error' => null,
            'settings' => [],
            'meta' => null,
        ];
    }
}
