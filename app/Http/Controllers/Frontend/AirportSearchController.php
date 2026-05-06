<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AirportSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $needle = Str::upper($q);
        $needleLike = mb_strtolower($q).'%';
        $containsLike = '%'.mb_strtolower($q).'%';

        $airports = Airport::query()
            ->active()
            ->withValidIata()
            ->commerciallySearchable()
            ->search($q)
            ->select([
                'iata_code',
                'icao_code',
                'name',
                'city',
                'country',
            ])
            ->selectRaw(
                'CASE
                    WHEN UPPER(COALESCE(iata_code, "")) = ? THEN 1
                    ELSE 0
                END as exact_iata_match,
                CASE
                    WHEN UPPER(COALESCE(icao_code, "")) = ? THEN 1
                    ELSE 0
                END as exact_icao_match,
                CASE
                    WHEN LOWER(COALESCE(city, "")) = ? THEN 800
                    WHEN LOWER(COALESCE(city, "")) LIKE ? THEN 760
                    WHEN LOWER(COALESCE(name, "")) LIKE ? THEN 750
                    WHEN LOWER(COALESCE(iata_code, "")) LIKE ? THEN 740
                    WHEN LOWER(COALESCE(icao_code, "")) LIKE ? THEN 730
                    WHEN LOWER(COALESCE(search_keywords, "")) LIKE ? THEN 640
                    ELSE 500
                END as rank_score',
                [
                    $needle,
                    $needle,
                    mb_strtolower($q),
                    $needleLike,
                    $needleLike,
                    $needleLike,
                    $needleLike,
                    $containsLike,
                ]
            )
            ->orderByDesc('exact_iata_match')
            ->orderByDesc('exact_icao_match')
            ->orderByDesc('priority_score')
            ->orderByDesc('route_count')
            ->orderByDesc('rank_score')
            ->orderBy('city')
            ->limit(15)
            ->get();

        return response()->json(
            $airports->map(static function (Airport $airport): array {
                $city = trim((string) ($airport->city ?? ''));
                $name = trim((string) ($airport->name ?? ''));
                $country = trim((string) ($airport->country ?? ''));

                return [
                    'iata_code' => $airport->iata_code,
                    'icao_code' => $airport->icao_code,
                    'label' => trim("{$city} — {$name}"),
                    'city' => $airport->city,
                    'country' => $airport->country,
                    'name' => $airport->name,
                ];
            })->values()->all()
        );
    }
}
