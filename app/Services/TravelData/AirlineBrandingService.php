<?php

namespace App\Services\TravelData;

use App\Models\Airline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AirlineBrandingService
{
    /**
     * Resolved logo URL: uploaded file under storage/app/public first, then optional CDN by IATA code.
     */
    public function getLogoForCode(?string $code): ?string
    {
        $stored = $this->getStoredLogoUrl($code);
        if ($stored !== null) {
            return $stored;
        }

        return $this->cdnLogoUrlForCode($code);
    }

    /**
     * Uploaded logo only (no CDN). Used when callers must avoid external URLs.
     */
    public function getStoredLogoUrl(?string $code): ?string
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $normalized = Str::upper(trim($code));
        $airline = Airline::query()
            ->active()
            ->where(function ($q) use ($normalized): void {
                $q->whereRaw('UPPER(COALESCE(iata_code, "")) = ?', [$normalized])
                    ->orWhereRaw('UPPER(COALESCE(icao_code, "")) = ?', [$normalized]);
            })
            ->first();

        if ($airline === null || $airline->logo_path === null) {
            return null;
        }
        if (! Storage::disk('public')->exists($airline->logo_path)) {
            return null;
        }

        return Storage::url($airline->logo_path);
    }

    /**
     * Public CDN URL for 2-letter IATA codes when enabled (e.g. Kiwi airline images).
     */
    public function cdnLogoUrlForCode(?string $code): ?string
    {
        if (! config('ota.airline_logo_cdn_enabled', true)) {
            return null;
        }

        $normalized = Str::upper(trim((string) $code));
        if (! preg_match('/^[A-Z0-9]{2}$/', $normalized)) {
            return null;
        }

        $template = (string) config(
            'ota.airline_logo_cdn_template',
            'https://images.kiwi.com/airlines/64x64/{CODE}.png'
        );

        return str_replace('{CODE}', $normalized, $template);
    }

    /**
     * @param  array<int, array<string, mixed>>|Collection<int, array<string, mixed>>  $offers
     * @return array<string, string>
     */
    public function mapLogosForOffers(array|Collection $offers): array
    {
        $rows = $offers instanceof Collection ? $offers->all() : $offers;
        $codes = collect($rows)
            ->map(function (array $offer): ?string {
                $primary = trim((string) ($offer['airline_code'] ?? ''));
                if ($primary !== '') {
                    return Str::upper($primary);
                }

                $fallback = trim((string) ($offer['carrier_code'] ?? ''));

                return $fallback !== '' ? Str::upper($fallback) : null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return [];
        }

        $airlines = Airline::query()
            ->active()
            ->where(function ($q) use ($codes): void {
                $q->whereIn('iata_code', $codes->all())
                    ->orWhereIn('icao_code', $codes->all());
            })
            ->get(['iata_code', 'icao_code', 'logo_path']);

        $map = [];
        foreach ($airlines as $airline) {
            if ($airline->logo_path === null || ! Storage::disk('public')->exists($airline->logo_path)) {
                continue;
            }
            $url = Storage::url($airline->logo_path);
            if ($airline->iata_code !== null) {
                $map[Str::upper($airline->iata_code)] = $url;
            }
            if ($airline->icao_code !== null) {
                $map[Str::upper($airline->icao_code)] = $url;
            }
        }

        foreach ($codes as $code) {
            $key = Str::upper((string) $code);
            if (isset($map[$key])) {
                continue;
            }
            $cdn = $this->cdnLogoUrlForCode($key);
            if ($cdn !== null) {
                $map[$key] = $cdn;
            }
        }

        return $map;
    }
}
