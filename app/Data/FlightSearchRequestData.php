<?php

namespace App\Data;

class FlightSearchRequestData
{
    public function __construct(
        public string $origin,
        public string $destination,
        public string $departure_date,
        public ?string $return_date = null,
        public string $trip_type = 'one_way',
        public int $adults = 1,
        public int $children = 0,
        public int $infants = 0,
        public string $cabin = 'economy',
        public string $currency = 'PKR',
        public ?int $agency_id = null,
        public string $source_channel = 'public_guest',
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     */
    public static function fromArray(array $criteria, ?int $agencyId = null, string $sourceChannel = 'public_guest'): self
    {
        return new self(
            origin: strtoupper((string) ($criteria['origin'] ?? 'LHE')),
            destination: strtoupper((string) ($criteria['destination'] ?? 'DXB')),
            departure_date: (string) ($criteria['depart_date'] ?? $criteria['departure_date'] ?? now()->addDays(14)->toDateString()),
            return_date: isset($criteria['return_date']) ? (string) $criteria['return_date'] : null,
            trip_type: (string) ($criteria['trip_type'] ?? 'one_way'),
            adults: (int) ($criteria['adults'] ?? 1),
            children: (int) ($criteria['children'] ?? 0),
            infants: (int) ($criteria['infants'] ?? 0),
            cabin: (string) ($criteria['cabin'] ?? 'economy'),
            currency: (string) ($criteria['currency'] ?? 'PKR'),
            agency_id: $agencyId,
            source_channel: $sourceChannel
        );
    }
}
