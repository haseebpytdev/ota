<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $fillable = [
        'iata_code',
        'icao_code',
        'name',
        'city',
        'country',
        'country_code',
        'airport_type',
        'timezone',
        'latitude',
        'longitude',
        'priority_score',
        'has_routes',
        'route_count',
        'is_commercial',
        'is_active',
        'search_keywords',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'bool',
            'priority_score' => 'int',
            'has_routes' => 'bool',
            'route_count' => 'int',
            'is_commercial' => 'bool',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $needle = mb_strtolower($term);

        return $query->where(function (Builder $q) use ($needle): void {
            $q->whereRaw('LOWER(COALESCE(iata_code, "")) LIKE ?', ["%{$needle}%"])
                ->orWhereRaw('LOWER(COALESCE(icao_code, "")) LIKE ?', ["%{$needle}%"])
                ->orWhereRaw('LOWER(COALESCE(name, "")) LIKE ?', ["%{$needle}%"])
                ->orWhereRaw('LOWER(COALESCE(city, "")) LIKE ?', ["%{$needle}%"])
                ->orWhereRaw('LOWER(COALESCE(country, "")) LIKE ?', ["%{$needle}%"])
                ->orWhereRaw('LOWER(COALESCE(search_keywords, "")) LIKE ?', ["%{$needle}%"]);
        });
    }

    public function scopeWithValidIata(Builder $query): Builder
    {
        return $query
            ->whereNotNull('iata_code')
            ->whereRaw('TRIM(iata_code) <> ""')
            ->whereRaw('UPPER(iata_code) NOT IN ("-", "---", "\\N", "N/A", "NULL")');
    }

    public function scopeCommerciallySearchable(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('has_routes', true)
                ->orWhere('priority_score', '>', 0)
                ->orWhere('is_commercial', true);
        });
    }
}
