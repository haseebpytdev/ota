<?php

namespace App\Services\Suppliers\Duffel;

use App\Data\BaggageAllowanceData;
use App\Data\FareBreakdownData;
use App\Data\NormalizedFlightOfferData;
use App\Enums\SupplierProvider;
use App\Models\SupplierConnection;
use App\Support\Security\SensitiveDataRedactor;

class DuffelOfferNormalizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return list<NormalizedFlightOfferData>
     */
    public function normalizeMany(array $payload, SupplierConnection $connection): array
    {
        $offers = [];
        foreach ($this->extractOfferRows($payload) as $row) {
            $mapped = $this->normalizeSingle($row, $connection);
            if ($mapped !== null) {
                $offers[] = $mapped;
            }
        }

        return $offers;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function normalizeOne(array $payload, SupplierConnection $connection): ?NormalizedFlightOfferData
    {
        $rows = $this->extractOfferRows($payload);

        return $rows === [] ? null : $this->normalizeSingle($rows[0], $connection);
    }

    /**
     * @param  array<string, mixed>  $offer
     */
    private function normalizeSingle(array $offer, SupplierConnection $connection): ?NormalizedFlightOfferData
    {
        $segments = $this->extractSegments($offer);
        if ($segments === []) {
            return null;
        }

        $first = $segments[0];
        $last = $segments[count($segments) - 1];
        $stops = max(0, count($segments) - 1);
        $base = $this->money($offer['base_amount'] ?? 0);
        $total = $this->money($offer['total_amount'] ?? 0);
        $tax = $this->money($offer['tax_amount'] ?? max(0, $total - $base));
        $fee = $this->money($offer['fee_amount'] ?? 0);
        $currency = strtoupper((string) ($offer['total_currency'] ?? $offer['currency'] ?? 'USD'));
        $ownerCode = strtoupper((string) ($offer['owner']['iata_code'] ?? $first['airline_code'] ?? 'XX'));
        $ownerName = trim((string) ($offer['owner']['name'] ?? $first['airline_name'] ?? $ownerCode));
        $offerId = trim((string) ($offer['id'] ?? ''));
        $durationMinutes = $this->durationMinutes((string) ($first['departure_at'] ?? ''), (string) ($last['arrival_at'] ?? ''));

        return new NormalizedFlightOfferData(
            offer_id: $offerId !== '' ? $offerId : sha1((string) json_encode($offer)),
            supplier_provider: SupplierProvider::Duffel->value,
            supplier_connection_id: $connection->id,
            airline_code: $ownerCode !== '' ? $ownerCode : 'XX',
            airline_name: $ownerName !== '' ? $ownerName : 'Duffel Partner',
            flight_number: isset($first['flight_number']) ? (string) $first['flight_number'] : null,
            origin: (string) ($first['origin'] ?? ''),
            destination: (string) ($last['destination'] ?? ''),
            departure_at: (string) ($first['departure_at'] ?? ''),
            arrival_at: (string) ($last['arrival_at'] ?? ''),
            duration_minutes: $durationMinutes,
            stops: $stops,
            cabin: strtolower((string) ($first['cabin'] ?? 'economy')),
            fare_family: isset($offer['fare_brand_name']) ? (string) $offer['fare_brand_name'] : null,
            refundable: (bool) ($offer['conditions']['refund_before_departure']['allowed'] ?? false),
            seats_left: isset($offer['available_services']) && is_array($offer['available_services']) ? count($offer['available_services']) : null,
            segments: $segments,
            baggage: new BaggageAllowanceData(
                summary: $this->baggageSummary($segments)
            ),
            fare_breakdown: new FareBreakdownData(
                base_fare: $base,
                taxes: $tax,
                supplier_fees: $fee,
                supplier_total: $total,
                currency: $currency
            ),
            expires_at: isset($offer['expires_at']) ? (string) $offer['expires_at'] : null,
            raw_reference: $offerId !== '' ? $offerId : null,
            raw_payload: SensitiveDataRedactor::redact($offer),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function extractOfferRows(array $payload): array
    {
        $rows = data_get($payload, 'data.offers', $payload['offers'] ?? null);
        if ($rows === null && isset($payload['data']) && is_array($payload['data']) && (($payload['data']['type'] ?? null) === 'offer')) {
            $rows = [$payload['data']];
        }
        if (! is_array($rows) && isset($payload['data']) && is_array($payload['data'])) {
            $rows = $payload['data'];
        }
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, static fn (mixed $row): bool => is_array($row)));
    }

    /**
     * @param  array<string, mixed>  $offer
     * @return list<array<string, int|string|null>>
     */
    private function extractSegments(array $offer): array
    {
        $segments = [];
        foreach ((array) ($offer['slices'] ?? []) as $slice) {
            if (! is_array($slice)) {
                continue;
            }
            foreach ((array) ($slice['segments'] ?? []) as $segment) {
                if (! is_array($segment)) {
                    continue;
                }
                $segments[] = [
                    'origin' => strtoupper((string) data_get($segment, 'origin.iata_code', '')),
                    'destination' => strtoupper((string) data_get($segment, 'destination.iata_code', '')),
                    'departure_at' => (string) data_get($segment, 'departing_at', ''),
                    'arrival_at' => (string) data_get($segment, 'arriving_at', ''),
                    'flight_number' => (string) data_get($segment, 'marketing_carrier_flight_number', ''),
                    'airline_code' => strtoupper((string) data_get($segment, 'marketing_carrier.iata_code', '')),
                    'airline_name' => (string) data_get($segment, 'marketing_carrier.name', ''),
                    'duration_minutes' => null,
                    'cabin' => (string) data_get($segment, 'cabin_class', ''),
                ];
            }
        }

        return $segments;
    }

    /**
     * @param  list<array<string, int|string|null>>  $segments
     */
    private function baggageSummary(array $segments): ?string
    {
        $parts = [];
        foreach ($segments as $segment) {
            $value = trim((string) ($segment['baggage'] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        if ($parts === []) {
            return null;
        }

        return implode(' · ', array_values(array_unique($parts)));
    }

    private function money(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '', trim($value));
        }

        return (float) $value;
    }

    private function durationMinutes(string $departureAt, string $arrivalAt): int
    {
        try {
            $start = new \DateTimeImmutable($departureAt);
            $end = new \DateTimeImmutable($arrivalAt);

            return max(0, (int) floor(($end->getTimestamp() - $start->getTimestamp()) / 60));
        } catch (\Throwable) {
            return 0;
        }
    }
}
