<?php

namespace App\Data;

class NormalizedFlightOfferData
{
    /**
     * @param  list<array<string, int|string|null>>  $segments
     * @param  array<string, mixed>|null  $raw_payload
     */
    public function __construct(
        public string $offer_id,
        public string $supplier_provider,
        public ?int $supplier_connection_id,
        public string $airline_code,
        public string $airline_name,
        public ?string $flight_number,
        public string $origin,
        public string $destination,
        public string $departure_at,
        public string $arrival_at,
        public int $duration_minutes,
        public int $stops,
        public string $cabin,
        public ?string $fare_family,
        public bool $refundable,
        public ?int $seats_left,
        public array $segments,
        public BaggageAllowanceData $baggage,
        public FareBreakdownData $fare_breakdown,
        public ?string $expires_at = null,
        public ?string $raw_reference = null,
        public ?array $raw_payload = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'offer_id' => $this->offer_id,
            'supplier_provider' => $this->supplier_provider,
            'supplier_connection_id' => $this->supplier_connection_id,
            'airline_code' => $this->airline_code,
            'airline_name' => $this->airline_name,
            'flight_number' => $this->flight_number,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'departure_at' => $this->departure_at,
            'arrival_at' => $this->arrival_at,
            'duration_minutes' => $this->duration_minutes,
            'stops' => $this->stops,
            'cabin' => $this->cabin,
            'fare_family' => $this->fare_family,
            'refundable' => $this->refundable,
            'seats_left' => $this->seats_left,
            'segments' => $this->segments,
            'baggage' => $this->baggage->toArray(),
            'fare_breakdown' => $this->fare_breakdown->toArray(),
            'expires_at' => $this->expires_at,
            'raw_reference' => $this->raw_reference,
            'raw_payload' => $this->raw_payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $baggage = is_array($data['baggage'] ?? null) ? $data['baggage'] : [];
        $fare = is_array($data['fare_breakdown'] ?? null) ? $data['fare_breakdown'] : [];

        return new self(
            offer_id: (string) ($data['offer_id'] ?? $data['id'] ?? ''),
            supplier_provider: (string) ($data['supplier_provider'] ?? ''),
            supplier_connection_id: isset($data['supplier_connection_id']) ? (int) $data['supplier_connection_id'] : null,
            airline_code: (string) ($data['airline_code'] ?? $data['carrier_code'] ?? 'XX'),
            airline_name: (string) ($data['airline_name'] ?? ''),
            flight_number: isset($data['flight_number']) ? (string) $data['flight_number'] : null,
            origin: (string) ($data['origin'] ?? ''),
            destination: (string) ($data['destination'] ?? ''),
            departure_at: (string) ($data['departure_at'] ?? $data['depart_at'] ?? ''),
            arrival_at: (string) ($data['arrival_at'] ?? $data['arrive_at'] ?? ''),
            duration_minutes: (int) ($data['duration_minutes'] ?? 0),
            stops: (int) ($data['stops'] ?? 0),
            cabin: (string) ($data['cabin'] ?? 'economy'),
            fare_family: isset($data['fare_family']) ? (string) $data['fare_family'] : null,
            refundable: (bool) ($data['refundable'] ?? false),
            seats_left: isset($data['seats_left']) ? (int) $data['seats_left'] : null,
            segments: is_array($data['segments'] ?? null) ? $data['segments'] : [],
            baggage: new BaggageAllowanceData(
                checked: isset($baggage['checked']) ? (string) $baggage['checked'] : null,
                cabin: isset($baggage['cabin']) ? (string) $baggage['cabin'] : null,
                summary: isset($baggage['summary']) ? (string) $baggage['summary'] : (is_string($data['baggage'] ?? null) ? $data['baggage'] : null),
            ),
            fare_breakdown: new FareBreakdownData(
                base_fare: (float) ($fare['base_fare'] ?? $data['base_fare'] ?? 0),
                taxes: (float) ($fare['taxes'] ?? $data['taxes'] ?? 0),
                supplier_fees: (float) ($fare['supplier_fees'] ?? 0),
                supplier_total: (float) ($fare['supplier_total'] ?? (($fare['base_fare'] ?? $data['base_fare'] ?? 0) + ($fare['taxes'] ?? $data['taxes'] ?? 0))),
                currency: (string) ($fare['currency'] ?? $data['currency'] ?? 'PKR'),
                passenger_pricing: is_array($fare['passenger_pricing'] ?? null) ? $fare['passenger_pricing'] : null,
                passenger_pricing_available: (bool) ($fare['passenger_pricing_available'] ?? false),
                passenger_counts: is_array($fare['passenger_counts'] ?? null) ? $fare['passenger_counts'] : [],
            ),
            expires_at: isset($data['expires_at']) ? (string) $data['expires_at'] : null,
            raw_reference: isset($data['raw_reference']) ? (string) $data['raw_reference'] : null,
            raw_payload: is_array($data['raw_payload'] ?? null) ? $data['raw_payload'] : null,
        );
    }
}
