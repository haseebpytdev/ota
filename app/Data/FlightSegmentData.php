<?php

namespace App\Data;

class FlightSegmentData
{
    public function __construct(
        public string $origin,
        public string $destination,
        public string $departure_at,
        public string $arrival_at,
        public ?string $flight_number = null,
        public ?string $airline_code = null,
        public ?string $airline_name = null,
        public int $duration_minutes = 0,
    ) {}

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'origin' => $this->origin,
            'destination' => $this->destination,
            'departure_at' => $this->departure_at,
            'arrival_at' => $this->arrival_at,
            'flight_number' => $this->flight_number,
            'airline_code' => $this->airline_code,
            'airline_name' => $this->airline_name,
            'duration_minutes' => $this->duration_minutes,
        ];
    }
}
