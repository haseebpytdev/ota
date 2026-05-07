<?php

namespace App\Support\FlightSearch;

use App\Models\Airport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Human-readable schedule labels for public flight cards and JSON (no raw ISO in UI fields).
 */
class FlightOfferDisplayPresenter
{
    /**
     * @param  list<string>  $iataCodes
     * @return array<string, string>
     */
    public static function airportCityMap(array $iataCodes): array
    {
        $codes = array_values(array_unique(array_filter(array_map(
            fn (mixed $c): string => strtoupper(trim((string) $c)),
            $iataCodes
        ))));
        if ($codes === []) {
            return [];
        }

        $rows = Airport::query()
            ->whereIn(DB::raw('UPPER(TRIM(iata_code))'), $codes)
            ->get(['iata_code', 'city']);

        $map = [];
        foreach ($rows as $row) {
            $map[strtoupper(trim((string) $row->iata_code))] = trim((string) ($row->city ?? ''));
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $offer
     * @return list<string>
     */
    public static function collectIataCodes(array $offer): array
    {
        $codes = [];
        $codes[] = (string) ($offer['origin'] ?? '');
        $codes[] = (string) ($offer['destination'] ?? '');
        foreach (is_array($offer['segments'] ?? null) ? $offer['segments'] : [] as $seg) {
            if (! is_array($seg)) {
                continue;
            }
            $codes[] = (string) ($seg['origin'] ?? '');
            $codes[] = (string) ($seg['destination'] ?? '');
        }

        return $codes;
    }

    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $criteria
     * @param  array<string, string>  $cityMap
     * @return array<string, mixed>
     */
    public static function buildPresentation(array $offer, array $criteria, array $cityMap): array
    {
        $depIso = (string) ($offer['depart_at'] ?? '');
        $arrIso = (string) ($offer['arrive_at'] ?? '');
        $segments = is_array($offer['segments'] ?? null) ? $offer['segments'] : [];

        $firstSeg = $segments[0] ?? null;
        $lastSeg = $segments !== [] ? $segments[count($segments) - 1] : null;

        $depCode = strtoupper(trim((string) (
            is_array($firstSeg)
                ? ($firstSeg['origin'] ?? '')
                : ($offer['origin'] ?? ($criteria['origin'] ?? ''))
        )));
        $arrCode = strtoupper(trim((string) (
            is_array($lastSeg)
                ? ($lastSeg['destination'] ?? '')
                : ($offer['destination'] ?? ($criteria['destination'] ?? ''))
        )));

        if ($depCode === '') {
            $depCode = strtoupper(trim((string) ($criteria['origin'] ?? '')));
        }
        if ($arrCode === '') {
            $arrCode = strtoupper(trim((string) ($criteria['destination'] ?? '')));
        }

        $depCity = $cityMap[$depCode] ?? '';
        $arrCity = $cityMap[$arrCode] ?? '';

        $depCarbon = self::safeCarbon($depIso);
        $arrCarbon = self::safeCarbon($arrIso);

        $depTimeDisplay = $depCarbon ? $depCarbon->format('H:i') : '';
        $arrTimeDisplay = $arrCarbon ? $arrCarbon->format('H:i') : '';
        $depDateDisplay = $depCarbon ? $depCarbon->format('D, j M') : '';
        $arrDateDisplay = $arrCarbon ? $arrCarbon->format('D, j M') : '';

        $arrivalDayOffset = null;
        if ($depCarbon && $arrCarbon) {
            $calDays = $depCarbon->copy()->startOfDay()->diffInDays($arrCarbon->copy()->startOfDay(), false);
            if ($calDays > 0) {
                $arrivalDayOffset = $calDays === 1 ? '+1 day' : '+'.$calDays.' days';
            }
        }

        $layoverLines = self::layoverSummaryLines(is_array($segments) ? $segments : []);

        $formattedSegments = [];
        foreach ($segments as $seg) {
            if (! is_array($seg)) {
                continue;
            }
            $sDep = self::safeCarbon((string) ($seg['departure_at'] ?? ''));
            $sArr = self::safeCarbon((string) ($seg['arrival_at'] ?? ''));
            $o = strtoupper(trim((string) ($seg['origin'] ?? '')));
            $d = strtoupper(trim((string) ($seg['destination'] ?? '')));
            $durMin = 0;
            if ($sDep && $sArr) {
                $durMin = max(0, (int) $sDep->diffInMinutes($sArr));
            }
            $formattedSegments[] = [
                'origin' => $o,
                'destination' => $d,
                'origin_city' => $cityMap[$o] ?? '',
                'destination_city' => $cityMap[$d] ?? '',
                'departure_time_display' => $sDep ? $sDep->format('H:i') : '',
                'departure_date_display' => $sDep ? $sDep->format('D, j M') : '',
                'arrival_time_display' => $sArr ? $sArr->format('H:i') : '',
                'arrival_date_display' => $sArr ? $sArr->format('D, j M') : '',
                'duration_display' => self::formatDurationMinutes($durMin),
                'flight_number' => (string) ($seg['flight_number'] ?? ''),
                'airline_code' => strtoupper((string) ($seg['airline_code'] ?? '')),
                'airline_name' => (string) ($seg['airline_name'] ?? ''),
                'operating_airline_code' => strtoupper((string) ($seg['operating_airline_code'] ?? '')),
                'operating_airline_name' => (string) ($seg['operating_airline_name'] ?? ''),
            ];
        }

        $bagChecked = trim((string) ($offer['baggage_checked'] ?? ''));
        $bagCabin = trim((string) ($offer['baggage_cabin'] ?? ''));
        $summaryOnly = trim((string) ($offer['baggage'] ?? ''));

        return [
            'departure_time_display' => $depTimeDisplay,
            'departure_date_display' => $depDateDisplay,
            'departure_airport_code' => $depCode,
            'departure_city' => $depCity,
            'arrival_time_display' => $arrTimeDisplay,
            'arrival_date_display' => $arrDateDisplay,
            'arrival_airport_code' => $arrCode,
            'arrival_city' => $arrCity,
            'arrival_day_offset' => $arrivalDayOffset,
            'layover_summary' => $layoverLines === [] ? null : implode(' · ', $layoverLines),
            'segments_display' => $formattedSegments,
            'baggage_checked_display' => $bagChecked !== '' ? $bagChecked : null,
            'baggage_cabin_display' => $bagCabin !== '' ? $bagCabin : null,
            'baggage_summary_display' => $summaryOnly !== '' ? $summaryOnly : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return list<string>
     */
    protected static function layoverSummaryLines(array $segments): array
    {
        $lines = [];
        $n = count($segments);
        for ($i = 0; $i < $n - 1; $i++) {
            $a = $segments[$i];
            $b = $segments[$i + 1];
            if (! is_array($a) || ! is_array($b)) {
                continue;
            }
            $arr = self::safeCarbon((string) ($a['arrival_at'] ?? ''));
            $dep = self::safeCarbon((string) ($b['departure_at'] ?? ''));
            $airport = strtoupper(trim((string) ($a['destination'] ?? '')));
            if (! $arr || ! $dep || $airport === '') {
                continue;
            }
            $mins = max(0, (int) $arr->diffInMinutes($dep));
            $lines[] = self::formatDurationMinutes($mins).' in '.$airport;
        }

        return $lines;
    }

    protected static function safeCarbon(string $iso): ?Carbon
    {
        if (trim($iso) === '') {
            return null;
        }
        try {
            return Carbon::parse($iso);
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function formatDurationMinutes(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $h.'h '.str_pad((string) $m, 2, '0', STR_PAD_LEFT).'m';
    }
}
