<?php

namespace App\Data;

class FareBreakdownData
{
    /**
     * @param  list<array<string, mixed>>|null  $passenger_pricing
     */
    public function __construct(
        public float $base_fare,
        public float $taxes,
        public float $supplier_fees,
        public float $supplier_total,
        public string $currency = 'PKR',
        public ?array $passenger_pricing = null,
        public bool $passenger_pricing_available = false,
        public array $passenger_counts = [],
    ) {}

    /**
     * @return array<string, float|string>
     */
    public function toArray(): array
    {
        return [
            'base_fare' => $this->base_fare,
            'taxes' => $this->taxes,
            'supplier_fees' => $this->supplier_fees,
            'supplier_total' => $this->supplier_total,
            'currency' => $this->currency,
            'passenger_pricing' => $this->passenger_pricing,
            'passenger_pricing_available' => $this->passenger_pricing_available || (is_array($this->passenger_pricing) && $this->passenger_pricing !== []),
            'has_passenger_pricing' => $this->passenger_pricing_available || (is_array($this->passenger_pricing) && $this->passenger_pricing !== []),
            'passenger_counts' => $this->passenger_counts,
        ];
    }
}
