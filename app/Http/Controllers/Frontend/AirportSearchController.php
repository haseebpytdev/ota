<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AirportSearchController extends Controller
{
    /**
     * Human-readable suggestion line (never a lone em dash / placeholder).
     */
    protected static function formatAirportLabel(Airport $airport): string
    {
        $city = trim((string) ($airport->city ?? ''));
        $name = trim((string) ($airport->name ?? ''));
        $code = strtoupper(trim((string) ($airport->iata_code ?? '')));

        if ($city !== '' && $name !== '') {
            return "{$city} — {$name}";
        }

        if ($name !== '') {
            return $name;
        }

        if ($city !== '') {
            return $city;
        }

        return $code !== '' ? "{$code}" : '';
    }

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $normalized = mb_strtolower(trim($q));
        $needle = Str::upper($normalized);
        $needleLike = $normalized.'%';
        $containsLike = '%'.$normalized.'%';
        $limit = max(1, min((int) $request->query('limit', 10), 15));
        $cacheKey = 'airport_search:'.md5($normalized.':'.$limit);

        $payload = Cache::remember($cacheKey, now()->addMinutes(20), function () use ($containsLike, $limit, $needle, $needleLike, $normalized) {
            $airports = Airport::query()
                ->active()
                ->withValidIata()
                ->commerciallySearchable()
                ->search($normalized)
                ->select([
                    'iata_code',
                    'icao_code',
                    'name',
                    'city',
                    'country',
                    'priority_score',
                    'route_count',
                ])
                ->selectRaw(
                    'CASE
                        WHEN UPPER(COALESCE(iata_code, "")) = ? THEN 1000
                        WHEN UPPER(COALESCE(icao_code, "")) = ? THEN 900
                        WHEN LOWER(COALESCE(city, "")) LIKE ? THEN 800
                        WHEN LOWER(COALESCE(name, "")) LIKE ? THEN 700
                        WHEN LOWER(COALESCE(name, "")) LIKE ? THEN 600
                        WHEN LOWER(COALESCE(country, "")) LIKE ? THEN 500
                        ELSE 100
                    END as rank_score',
                    [
                        $needle,
                        $needle,
                        $needleLike,
                        $needleLike,
                        $containsLike,
                        $containsLike,
                    ]
                )
                ->orderByDesc('rank_score')
                ->orderByDesc('priority_score')
                ->orderByDesc('route_count')
                ->orderBy('city')
                ->limit($limit)
                ->get();

            $mapped = $airports->map(static function (Airport $airport): array {
                $iata = strtoupper((string) $airport->iata_code);
                $city = trim((string) ($airport->city ?? ''));
                $name = trim((string) ($airport->name ?? ''));
                $country = trim((string) ($airport->country ?? ''));
                $cityLabel = $city !== '' ? $city : $name;

                return [
                    'iata' => $iata,
                    'iata_code' => $iata, // backward-compatible key for older tests/consumers
                    'name' => $name,
                    'city' => $city,
                    'country' => $country,
                    'label' => trim($cityLabel.' ('.$iata.')'),
                    'description' => trim($name.($country !== '' ? ' · '.$country : '')),
                ];
            })->filter(static fn (array $row): bool => ($row['iata'] ?? '') !== '')->values()->all();

            if ($mapped === []) {
                Cache::put('airport_search_empty:'.md5($normalized), true, now()->addMinutes(3));
            }

            return $mapped;
        });

        return response()->json($payload);
    }
}
