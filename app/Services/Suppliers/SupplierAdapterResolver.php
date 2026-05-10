<?php

namespace App\Services\Suppliers;

use App\Contracts\Suppliers\FlightSupplierInterface;
use App\Enums\SupplierProvider;
use App\Services\Suppliers\Adapters\AirlineDirectFlightSupplierAdapter;
use App\Services\Suppliers\Adapters\DuffelFlightSupplierAdapter;
use App\Services\Suppliers\Adapters\PiaFlightSupplierAdapter;
use App\Services\Suppliers\Adapters\SabreFlightSupplierAdapter;
use InvalidArgumentException;

class SupplierAdapterResolver
{
    public function __construct(
        protected SabreFlightSupplierAdapter $sabreAdapter,
        protected PiaFlightSupplierAdapter $piaAdapter,
        protected AirlineDirectFlightSupplierAdapter $airlineDirectAdapter,
        protected DuffelFlightSupplierAdapter $duffelAdapter,
    ) {}

    public function resolve(SupplierProvider $provider): FlightSupplierInterface
    {
        return match ($provider) {
            SupplierProvider::Sabre => $this->sabreAdapter,
            SupplierProvider::Pia => $this->piaAdapter,
            SupplierProvider::AirlineDirect => $this->airlineDirectAdapter,
            SupplierProvider::Duffel => $this->duffelAdapter,
            default => throw new InvalidArgumentException('Unsupported supplier provider: '.$provider->value),
        };
    }
}
