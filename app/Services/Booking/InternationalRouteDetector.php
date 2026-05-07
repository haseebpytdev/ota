<?php

namespace App\Services\Booking;

use App\Models\Airport;

/**
 * Determines whether a public itinerary crosses a country border using airport reference data.
 */
class InternationalRouteDetector
{
    public function isInternational(?string $originIata, ?string $destinationIata): bool
    {
        $o = strtoupper(trim((string) $originIata));
        $d = strtoupper(trim((string) $destinationIata));

        if ($o === '' || $d === '') {
            return false;
        }

        $originAirport = Airport::query()->where('iata_code', $o)->first();
        $destAirport = Airport::query()->where('iata_code', $d)->first();

        if ($originAirport === null || $destAirport === null) {
            return false;
        }

        $oc = $this->normalizeCountryKey($originAirport);
        $dc = $this->normalizeCountryKey($destAirport);

        if ($oc === '' || $dc === '') {
            return true;
        }

        return strcasecmp($oc, $dc) !== 0;
    }

    protected function normalizeCountryKey(Airport $airport): string
    {
        $code = trim((string) ($airport->country_code ?? ''));
        if ($code !== '') {
            return strtoupper($code);
        }

        return strtolower(trim((string) ($airport->country ?? '')));
    }
}
