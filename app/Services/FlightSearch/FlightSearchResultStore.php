<?php

namespace App\Services\FlightSearch;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FlightSearchResultStore
{
    private const CACHE_PREFIX = 'flight_search:';

    private const TTL_SECONDS = 1800;

    private const MAX_STORED_OFFERS = 150;

    /**
     * @param  list<array<string, mixed>>  $offers
     * @param  list<string>  $warnings
     * @param  array<string, mixed>  $criteria
     */
    public function store(array $criteria, array $offers, array $warnings): string
    {
        $searchId = (string) Str::uuid();
        $trimmedOffers = array_slice($offers, 0, self::MAX_STORED_OFFERS);

        Cache::put($this->key($searchId), [
            'search_id' => $searchId,
            'criteria' => $criteria,
            'offers' => $trimmedOffers,
            'warnings' => array_values(array_unique($warnings)),
            'created_at' => now()->toIso8601String(),
        ], self::TTL_SECONDS);

        return $searchId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $searchId): ?array
    {
        $payload = Cache::get($this->key($searchId));
        if (! is_array($payload)) {
            return null;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOffer(string $searchId, string $offerId): ?array
    {
        $payload = $this->get($searchId);
        if ($payload === null) {
            return null;
        }

        $offers = $payload['offers'] ?? [];
        if (! is_array($offers)) {
            return null;
        }

        foreach ($offers as $offer) {
            if (! is_array($offer)) {
                continue;
            }
            if ((string) ($offer['id'] ?? '') === $offerId || (string) ($offer['offer_id'] ?? '') === $offerId) {
                return $offer;
            }
        }

        return null;
    }

    private function key(string $searchId): string
    {
        return self::CACHE_PREFIX.$searchId;
    }
}
