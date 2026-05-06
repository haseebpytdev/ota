<?php

namespace App\Data;

class FareBreakdownData
{
    public function __construct(
        public float $base_fare,
        public float $taxes,
        public float $supplier_fees,
        public float $supplier_total,
        public string $currency = 'PKR',
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
        ];
    }
}
