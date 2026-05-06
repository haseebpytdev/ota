<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicFlightSearchRequest;
use App\Models\Agency;
use App\Services\FlightSearch\FlightDeparturePolicy;
use App\Services\FlightSearch\FlightSearchResultStore;
use App\Services\FlightSearch\FlightSearchService;
use App\Services\TravelData\AirlineBrandingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FlightController extends Controller
{
    public function __construct(
        protected FlightSearchService $flightSearch,
        protected FlightSearchResultStore $searchStore,
        protected AirlineBrandingService $airlineBranding,
        protected FlightDeparturePolicy $departurePolicy,
    ) {}

    public function search(Request $request): View
    {
        return view('frontend.flights.search', [
            'defaults' => [
                'origin' => $request->string('from')->toString(),
                'destination' => $request->string('to')->toString(),
                'depart' => $request->string('depart')->toString(),
                'return_date' => $request->string('return_date')->toString(),
                'trip_type' => $request->string('trip_type', 'one_way')->toString(),
            ],
            'minDate' => now()->format('Y-m-d'),
        ]);
    }

    public function results(PublicFlightSearchRequest $request): View
    {
        $criteria = $request->criteria();

        $agency = Agency::query()->where('slug', config('ota.default_agency_slug'))->first();
        $result = $this->flightSearch->searchWithMeta($criteria, $agency, 'public_guest');
        $searchId = $this->searchStore->store($criteria, $result['offers'], $result['warnings'] ?? []);
        $warnings = [];
        foreach ($result['warnings'] ?? [] as $w) {
            $line = (string) $w;
            if ($line === '') {
                continue;
            }
            if (str_contains($line, 'Duffel') && str_contains($line, 'unavailable')) {
                $warnings[] = 'Provider search is temporarily unavailable.';
            } elseif (str_contains($line, 'Provider search is temporarily unavailable')) {
                $warnings[] = 'Provider search is temporarily unavailable.';
            } else {
                $warnings[] = $line;
            }
        }
        $warnings = array_values(array_unique($warnings));

        $providers = collect($result['offers'])
            ->map(fn (array $offer): string => (string) ($offer['supplier_provider'] ?? 'unknown'))
            ->countBy()
            ->all();
        $supplierTotals = collect($result['offers'])->map(fn (array $o): float => (float) ($o['base_fare'] + $o['taxes']));
        $finalTotals = collect($result['offers'])->map(fn (array $o): float => (float) ($o['final_customer_price'] ?? 0));
        Log::info('flight_search.completed', [
            'providers' => $providers,
            'offers_count' => count($result['offers']),
            'min_supplier_total' => $supplierTotals->isEmpty() ? null : $supplierTotals->min(),
            'min_final_customer_price' => $finalTotals->isEmpty() ? null : $finalTotals->min(),
            'currency' => collect($result['offers'])->pluck('currency')->filter()->first(),
            'conversion_statuses' => collect($result['offers'])->pluck('conversion_status')->filter()->values()->all(),
            'offer_pricing_preview' => collect($result['offers'])->take(5)->map(function (array $offer): array {
                return [
                    'provider' => (string) ($offer['supplier_provider'] ?? 'unknown'),
                    'supplier_currency' => (string) ($offer['supplier_currency'] ?? $offer['currency'] ?? ''),
                    'supplier_total' => (float) ($offer['supplier_total_source'] ?? (($offer['base_fare'] ?? 0) + ($offer['taxes'] ?? 0))),
                    'pricing_currency' => (string) ($offer['pricing_currency'] ?? $offer['currency'] ?? ''),
                    'markup' => (float) ($offer['markup'] ?? 0),
                    'service_fee' => (float) ($offer['service_fee'] ?? 0),
                    'final_customer_price' => (float) ($offer['final_customer_price'] ?? 0),
                    'conversion_status' => (string) ($offer['conversion_status'] ?? 'unknown'),
                ];
            })->values()->all(),
            'duration_ms' => null,
        ]);

        return view('frontend.flights.results', [
            'criteria' => $criteria,
            'searchId' => $searchId,
            'warnings' => $warnings,
            'searchSummary' => $this->formatSearchSummary($criteria),
        ]);
    }

    public function resultsData(Request $request): JsonResponse
    {
        $searchId = trim((string) $request->query('search_id', ''));
        if ($searchId === '') {
            return response()->json([
                'message' => 'Missing search_id.',
                'offers' => [],
            ], 422);
        }

        $payload = $this->searchStore->get($searchId);
        if ($payload === null) {
            return response()->json([
                'message' => 'This fare search has expired. Please search again.',
                'offers' => [],
                'total' => 0,
                'has_more' => false,
            ], 410);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 12);
        if ($perPage < 1) {
            $perPage = 12;
        }
        $perPage = min($perPage, 25);
        $sort = (string) $request->query('sort', 'recommended');
        $filters = [
            'airline' => strtoupper(trim((string) $request->query('airline', ''))),
            'stops' => trim((string) $request->query('stops', '')),
            'refundable' => trim((string) $request->query('refundable', '')),
            'cabin' => trim((string) $request->query('cabin', '')),
            'baggage' => trim((string) $request->query('baggage', '')),
            'departure_window' => trim((string) $request->query('departure_window', '')),
            'arrival_window' => trim((string) $request->query('arrival_window', '')),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'max_duration' => $request->query('max_duration'),
            'duration_bucket' => trim((string) $request->query('duration_bucket', '')),
            'layover_airport' => strtoupper(trim((string) $request->query('layover_airport', ''))),
            'fare_family' => trim((string) $request->query('fare_family', '')),
            'bookable_only' => trim((string) $request->query('bookable_only', '')),
            'operating_airline' => strtoupper(trim((string) $request->query('operating_airline', ''))),
        ];

        /** @var list<array<string, mixed>> $offers */
        $offers = is_array($payload['offers'] ?? null) ? $payload['offers'] : [];
        $critForFilters = is_array($payload['criteria'] ?? null) ? $payload['criteria'] : [];
        $offers = $this->filterOffers($offers, $filters, $critForFilters);
        $offers = $this->sortOffers($offers, $sort, $critForFilters);
        $total = count($offers);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($offers, $offset, $perPage);
        $filterMeta = $this->buildFilterMeta($offers, $critForFilters);

        $airlineLogos = $this->airlineBranding->mapLogosForOffers($slice);
        $data = array_map(function (array $offer) use ($payload, $searchId, $airlineLogos): array {
            $crit = is_array($payload['criteria'] ?? null) ? $payload['criteria'] : [];
            $code = strtoupper((string) ($offer['airline_code'] ?? ($offer['carrier_code'] ?? '')));
            $supplierTotal = (float) ($offer['supplier_total_source'] ?? (($offer['base_fare'] ?? 0) + ($offer['taxes'] ?? 0)));
            $markup = (float) ($offer['markup'] ?? 0);
            $serviceFee = (float) ($offer['service_fee'] ?? 0);
            $final = (float) ($offer['final_customer_price'] ?? 0);
            $pricingCurrency = strtoupper((string) ($offer['pricing_currency'] ?? $offer['currency'] ?? 'PKR'));
            $conversionStatus = (string) ($offer['conversion_status'] ?? 'same_currency');
            $hasPkrFare = $this->offerHasConfirmedPkrFare($offer);
            $canSelect = $this->offerIsCustomerBookable($offer, $crit);
            $priceDisplay = $hasPkrFare
                ? 'Rs '.number_format($final, 0).' PKR'
                : 'PKR fare unavailable';
            $priceNote = ! $hasPkrFare
                ? ($conversionStatus === 'conversion_missing'
                    ? 'Fares are quoted in Pakistani Rupees (PKR). PKR pricing could not be confirmed for this option—contact support.'
                    : 'Fares are quoted in Pakistani Rupees (PKR). This option cannot be priced in PKR online.')
                : ($canSelect
                    ? 'Total in PKR including taxes, markup, and service fee.'
                    : FlightDeparturePolicy::SAME_DAY_LEAD_MESSAGE);

            $disabledReason = $canSelect
                ? null
                : (! $hasPkrFare
                    ? ($conversionStatus === 'conversion_missing'
                        ? 'PKR fare not confirmed for this option.'
                        : 'PKR fare not available online for this option.')
                    : FlightDeparturePolicy::SAME_DAY_LEAD_MESSAGE);

            return [
                'offer_id' => (string) ($offer['id'] ?? $offer['offer_id'] ?? ''),
                'provider' => (string) ($offer['supplier_provider'] ?? 'unknown'),
                'airline_code' => $code,
                'airline_name' => (string) ($offer['airline_name'] ?? ''),
                'airline_logo_url' => $airlineLogos[$code] ?? null,
                'route' => ($payload['criteria']['origin'] ?? '').' → '.($payload['criteria']['destination'] ?? ''),
                'departure_time' => (string) ($offer['depart_at'] ?? ''),
                'arrival_time' => (string) ($offer['arrive_at'] ?? ''),
                'duration' => ((int) ($offer['duration_h'] ?? 0)).'h '.str_pad((string) ((int) ($offer['duration_m'] ?? 0)), 2, '0', STR_PAD_LEFT).'m',
                'stops' => (int) ($offer['stops'] ?? 0),
                'baggage' => (string) ($offer['baggage'] ?? ''),
                'refundable' => (bool) ($offer['refundable'] ?? false),
                'currency' => (string) ($offer['currency'] ?? 'PKR'),
                'supplier_currency' => (string) ($offer['supplier_currency'] ?? $pricingCurrency),
                'supplier_total' => $supplierTotal,
                'markup' => $markup,
                'service_fee' => $serviceFee,
                'final_customer_price' => $final,
                'pricing_currency' => $pricingCurrency,
                'conversion_status' => $conversionStatus,
                'fx_rate' => $offer['pricing_components']['fx_rate'] ?? null,
                'price_display' => $priceDisplay,
                'price_note' => $priceNote,
                'can_book' => $canSelect,
                'disabled_reason' => $disabledReason,
                'flight_number' => (string) ($offer['flight_number'] ?? ''),
                'cabin' => (string) ($offer['cabin'] ?? ''),
                'fare_family' => (string) ($offer['fare_family'] ?? ''),
                'operating_airline_code' => strtoupper((string) ($offer['operating_carrier_code'] ?? $offer['operating_airline_code'] ?? '')),
                'segments' => array_values(array_map(function (array $seg): array {
                    return [
                        'origin' => (string) ($seg['origin'] ?? ''),
                        'destination' => (string) ($seg['destination'] ?? ''),
                        'departure_at' => (string) ($seg['departure_at'] ?? ''),
                        'arrival_at' => (string) ($seg['arrival_at'] ?? ''),
                        'airline_code' => (string) ($seg['airline_code'] ?? ''),
                        'flight_number' => (string) ($seg['flight_number'] ?? ''),
                    ];
                }, is_array($offer['segments'] ?? null) ? $offer['segments'] : [])),
                'select_url' => $canSelect ? route('booking.passengers', array_merge([
                    'flight_id' => (string) ($offer['id'] ?? $offer['offer_id'] ?? ''),
                    'search_id' => $searchId,
                    'offer_id' => (string) ($offer['id'] ?? $offer['offer_id'] ?? ''),
                    'from' => (string) ($crit['origin'] ?? ''),
                    'to' => (string) ($crit['destination'] ?? ''),
                    'depart' => (string) ($crit['depart_date'] ?? ''),
                    'trip_type' => (string) ($crit['trip_type'] ?? 'one_way'),
                    'cabin' => (string) ($crit['cabin'] ?? 'economy'),
                    'adults' => (int) ($crit['adults'] ?? 1),
                    'children' => (int) ($crit['children'] ?? 0),
                    'infants' => (int) ($crit['infants'] ?? 0),
                ], (($rd = trim((string) ($crit['return_date'] ?? ''))) !== '' ? ['return_date' => $rd] : []))) : null,
            ];
        }, $slice);

        return response()->json([
            'search_id' => $searchId,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => ($offset + $perPage) < $total,
            'filters' => $filterMeta,
            'offers' => $data,
            'warnings' => [],
        ]);
    }

    public function details(Request $request, string $id): View
    {
        $criteria = [
            'origin' => $request->string('from')->toString(),
            'destination' => $request->string('to')->toString(),
            'depart_date' => $request->string('depart')->toString(),
            'trip_type' => $request->string('trip_type', 'one_way')->toString(),
            'cabin' => $request->string('cabin', 'economy')->toString(),
            'adults' => max(1, (int) $request->input('adults', 1)),
            'children' => max(0, (int) $request->input('children', 0)),
            'infants' => max(0, (int) $request->input('infants', 0)),
            'return_date' => $request->filled('return_date') ? $request->string('return_date')->toString() : null,
        ];

        if ($criteria['origin'] === '' || $criteria['destination'] === '' || $criteria['depart_date'] === '') {
            abort(404);
        }

        try {
            if (Carbon::parse($criteria['depart_date'])->startOfDay()->lt(now()->startOfDay())) {
                abort(404);
            }
        } catch (\Throwable) {
            abort(404);
        }

        $agency = Agency::query()->where('slug', config('ota.default_agency_slug'))->first();
        $enriched = $this->flightSearch->search($criteria, $agency, 'public_guest');
        $offer = collect($enriched)->firstWhere('id', $id);

        abort_if($offer === null, 404);

        $logo = $this->airlineBranding->getLogoForCode((string) ($offer['airline_code'] ?? ($offer['carrier_code'] ?? '')));

        $canContinueBooking = $this->offerIsCustomerBookable($offer, $criteria);

        return view('frontend.flights.details', [
            'offer' => $offer,
            'criteria' => $criteria,
            'airlineLogo' => $logo,
            'canContinueBooking' => $canContinueBooking,
            'bookingBlockedReason' => $canContinueBooking ? null : (! $this->offerHasConfirmedPkrFare($offer)
                ? 'This fare cannot be booked online until a PKR total is confirmed.'
                : FlightDeparturePolicy::SAME_DAY_LEAD_MESSAGE),
        ]);
    }

    /**
     * Confirmed PKR customer quote (positive total, converted or native PKR).
     *
     * @param  array<string, mixed>  $offer
     */
    protected function offerHasConfirmedPkrFare(array $offer): bool
    {
        $final = (float) ($offer['final_customer_price'] ?? $offer['total'] ?? 0);
        $pricingCurrency = strtoupper((string) ($offer['pricing_currency'] ?? $offer['currency'] ?? 'PKR'));
        $conversionStatus = (string) ($offer['conversion_status'] ?? 'same_currency');

        return $final > 0
            && $pricingCurrency === 'PKR'
            && in_array($conversionStatus, ['same_currency', 'converted'], true);
    }

    /**
     * Book / checkout allowed only for confirmed PKR fares that meet departure lead-time rules.
     *
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $criteria
     */
    protected function offerIsCustomerBookable(array $offer, array $criteria): bool
    {
        if (! $this->offerHasConfirmedPkrFare($offer)) {
            return false;
        }

        return $this->departurePolicy->offerMeetsLeadTimeForBooking($offer, $criteria);
    }

    /**
     * @param  list<array<string, mixed>>  $offers
     * @return list<array<string, mixed>>
     */
    protected function sortOffers(array $offers, string $sort, array $criteria = []): array
    {
        if ($sort === '') {
            return $offers;
        }

        usort($offers, function (array $a, array $b) use ($sort, $criteria): int {
            $aCanBook = $this->offerIsCustomerBookable($a, $criteria);
            $bCanBook = $this->offerIsCustomerBookable($b, $criteria);
            if ($aCanBook !== $bCanBook) {
                return $aCanBook ? -1 : 1;
            }

            return match ($sort) {
                'price_desc' => (float) ($b['final_customer_price'] ?? 0) <=> (float) ($a['final_customer_price'] ?? 0),
                'departure_time', 'earliest_departure' => strcmp((string) ($a['depart_at'] ?? ''), (string) ($b['depart_at'] ?? '')),
                'latest_departure' => strcmp((string) ($b['depart_at'] ?? ''), (string) ($a['depart_at'] ?? '')),
                'arrival_time' => strcmp((string) ($a['arrive_at'] ?? ''), (string) ($b['arrive_at'] ?? '')),
                'duration', 'fastest' => ((int) ($a['duration_h'] ?? 0) * 60 + (int) ($a['duration_m'] ?? 0)) <=> ((int) ($b['duration_h'] ?? 0) * 60 + (int) ($b['duration_m'] ?? 0)),
                'airline_name', 'airline_az' => strcmp((string) ($a['airline_name'] ?? ''), (string) ($b['airline_name'] ?? '')),
                default => (float) ($a['final_customer_price'] ?? 0) <=> (float) ($b['final_customer_price'] ?? 0),
            };
        });

        return $offers;
    }

    /**
     * @param  list<array<string, mixed>>  $offers
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    protected function filterOffers(array $offers, array $filters, array $criteria = []): array
    {
        return array_values(array_filter($offers, function (array $offer) use ($filters, $criteria): bool {
            if (($filters['airline'] ?? '') !== '') {
                $code = strtoupper((string) ($offer['airline_code'] ?? ($offer['carrier_code'] ?? '')));
                if ($code !== $filters['airline']) {
                    return false;
                }
            }

            if (($filters['stops'] ?? '') !== '') {
                $stops = (int) ($offer['stops'] ?? 0);
                if ($filters['stops'] === 'direct' && $stops !== 0) {
                    return false;
                }
                if ($filters['stops'] === '1_stop' && $stops !== 1) {
                    return false;
                }
                if ($filters['stops'] === '2_plus' && $stops < 2) {
                    return false;
                }
            }

            if (($filters['operating_airline'] ?? '') !== '') {
                $op = strtoupper((string) ($offer['operating_carrier_code'] ?? $offer['operating_airline_code'] ?? ''));
                if ($op === '' || $op !== $filters['operating_airline']) {
                    return false;
                }
            }

            if (($filters['refundable'] ?? '') !== '') {
                $refundable = (bool) ($offer['refundable'] ?? false);
                if ((string) $filters['refundable'] === '1' && ! $refundable) {
                    return false;
                }
                if ((string) $filters['refundable'] === '0' && $refundable) {
                    return false;
                }
            }

            if (($filters['cabin'] ?? '') !== '' && strtolower((string) ($offer['cabin'] ?? '')) !== strtolower((string) $filters['cabin'])) {
                return false;
            }

            if (($filters['fare_family'] ?? '') !== '' && strtolower((string) ($offer['fare_family'] ?? '')) !== strtolower((string) $filters['fare_family'])) {
                return false;
            }

            if (($filters['bookable_only'] ?? '') !== '') {
                $bookable = $this->offerIsCustomerBookable($offer, $criteria);
                if ((string) $filters['bookable_only'] === '1' && ! $bookable) {
                    return false;
                }
            }

            if (($filters['baggage'] ?? '') !== '') {
                $bag = strtolower((string) ($offer['baggage'] ?? ''));
                $bucket = str_contains($bag, 'kg') ? 'checked_baggage' : ($bag !== '' ? 'cabin_baggage' : 'no_baggage_info');
                if ($bucket !== $filters['baggage']) {
                    return false;
                }
            }

            if (($filters['departure_window'] ?? '') !== '' && ! $this->matchesTimeWindow((string) ($offer['depart_at'] ?? ''), (string) $filters['departure_window'])) {
                return false;
            }

            if (($filters['arrival_window'] ?? '') !== '' && ! $this->matchesTimeWindow((string) ($offer['arrive_at'] ?? ''), (string) $filters['arrival_window'])) {
                return false;
            }

            $durationMinutes = ((int) ($offer['duration_h'] ?? 0) * 60) + (int) ($offer['duration_m'] ?? 0);
            if ($filters['max_duration'] !== null && $filters['max_duration'] !== '' && $durationMinutes > (int) $filters['max_duration']) {
                return false;
            }

            if (($filters['duration_bucket'] ?? '') !== '' && ! $this->matchesDurationBucket($durationMinutes, (string) $filters['duration_bucket'])) {
                return false;
            }

            if (($filters['layover_airport'] ?? '') !== '') {
                $segments = is_array($offer['segments'] ?? null) ? $offer['segments'] : [];
                $layovers = $this->layoverAirportsFromSegments($segments);
                if (! in_array((string) $filters['layover_airport'], $layovers, true)) {
                    return false;
                }
            }

            $price = (float) ($offer['final_customer_price'] ?? 0);
            if ($filters['min_price'] !== null && $filters['min_price'] !== '' && $price < (float) $filters['min_price']) {
                return false;
            }
            if ($filters['max_price'] !== null && $filters['max_price'] !== '' && $price > (float) $filters['max_price']) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param  list<array<string, mixed>>  $offers
     * @return array<string, mixed>
     */
    protected function buildFilterMeta(array $offers, array $criteria = []): array
    {
        $airlineCounts = [];
        $direct = 0;
        $oneStop = 0;
        $twoPlus = 0;
        $refundable = 0;
        $nonRefundable = 0;
        $prices = [];
        $cabinCounts = [];
        $baggageCounts = ['checked_baggage' => 0, 'cabin_baggage' => 0, 'no_baggage_info' => 0];
        $departureWindows = ['early_morning' => 0, 'morning' => 0, 'afternoon' => 0, 'evening' => 0];
        $arrivalWindows = ['early_morning' => 0, 'morning' => 0, 'afternoon' => 0, 'evening' => 0];
        $durations = [];
        $durationBuckets = ['under_6h' => 0, '6_12h' => 0, '12_20h' => 0, 'over_20h' => 0];
        $layovers = [];
        $fareFamilies = [];
        $operatingCounts = [];
        $bookable = 0;
        $unavailable = 0;
        foreach ($offers as $offer) {
            $code = strtoupper((string) ($offer['airline_code'] ?? ($offer['carrier_code'] ?? '')));
            $name = (string) ($offer['airline_name'] ?? $code);
            if ($code !== '') {
                if (! isset($airlineCounts[$code])) {
                    $airlineCounts[$code] = ['code' => $code, 'name' => $name, 'count' => 0];
                }
                $airlineCounts[$code]['count']++;
            }
            $stops = (int) ($offer['stops'] ?? 0);
            if ($stops === 0) {
                $direct++;
            }
            if ($stops === 1) {
                $oneStop++;
            }
            if ($stops >= 2) {
                $twoPlus++;
            }
            if ((bool) ($offer['refundable'] ?? false)) {
                $refundable++;
            } else {
                $nonRefundable++;
            }
            $isBookable = $this->offerIsCustomerBookable($offer, $criteria);
            if ($isBookable) {
                $prices[] = (float) ($offer['final_customer_price'] ?? 0);
                $bookable++;
            } else {
                $unavailable++;
            }

            $cabin = strtolower((string) ($offer['cabin'] ?? ''));
            if ($cabin !== '') {
                $cabinCounts[$cabin] = ($cabinCounts[$cabin] ?? 0) + 1;
            }

            $bag = strtolower((string) ($offer['baggage'] ?? ''));
            $bagKey = str_contains($bag, 'kg') ? 'checked_baggage' : ($bag !== '' ? 'cabin_baggage' : 'no_baggage_info');
            $baggageCounts[$bagKey]++;

            $depWindow = $this->timeWindow((string) ($offer['depart_at'] ?? ''));
            $arrWindow = $this->timeWindow((string) ($offer['arrive_at'] ?? ''));
            if ($depWindow !== null) {
                $departureWindows[$depWindow]++;
            }
            if ($arrWindow !== null) {
                $arrivalWindows[$arrWindow]++;
            }

            $durationMinutes = ((int) ($offer['duration_h'] ?? 0) * 60) + (int) ($offer['duration_m'] ?? 0);
            $durations[] = $durationMinutes;
            $durationBuckets[$this->durationBucket($durationMinutes)]++;

            $segments = is_array($offer['segments'] ?? null) ? $offer['segments'] : [];
            foreach ($this->layoverAirportsFromSegments($segments) as $layCode) {
                $layovers[$layCode] = ($layovers[$layCode] ?? 0) + 1;
            }

            $fareFamily = trim((string) ($offer['fare_family'] ?? ''));
            if ($fareFamily !== '') {
                $fareFamilies[$fareFamily] = ($fareFamilies[$fareFamily] ?? 0) + 1;
            }

            $opCode = strtoupper((string) ($offer['operating_carrier_code'] ?? $offer['operating_airline_code'] ?? ''));
            if ($opCode !== '') {
                $operatingCounts[$opCode] = ($operatingCounts[$opCode] ?? 0) + 1;
            }
        }

        uasort($airlineCounts, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return [
            'airlines' => array_values($airlineCounts),
            'stops' => [
                ['value' => 'direct', 'count' => $direct],
                ['value' => '1_stop', 'count' => $oneStop],
                ['value' => '2_plus', 'count' => $twoPlus],
            ],
            'refundable' => [
                ['value' => true, 'count' => $refundable],
                ['value' => false, 'count' => $nonRefundable],
            ],
            'price_range' => [
                'min' => $prices === [] ? 0 : min($prices),
                'max' => $prices === [] ? 0 : max($prices),
                'currency' => 'PKR',
            ],
            'cabin_classes' => collect($cabinCounts)->map(fn (int $count, string $cabinKey): array => [
                'value' => $cabinKey,
                'label' => $this->cabinMetaLabel($cabinKey),
                'count' => $count,
            ])->values()->all(),
            'operating_airlines' => collect($operatingCounts)->map(fn (int $count, string $code): array => [
                'code' => $code,
                'label' => $code,
                'count' => $count,
            ])->values()->all(),
            'baggage_options' => [
                ['value' => 'checked_baggage', 'label' => 'Checked baggage included', 'count' => $baggageCounts['checked_baggage']],
                ['value' => 'cabin_baggage', 'label' => 'Cabin baggage only', 'count' => $baggageCounts['cabin_baggage']],
                ['value' => 'no_baggage_info', 'label' => 'No baggage info', 'count' => $baggageCounts['no_baggage_info']],
            ],
            'departure_time_windows' => $this->windowMeta($departureWindows),
            'arrival_time_windows' => $this->windowMeta($arrivalWindows),
            'duration_range' => [
                'min_duration_minutes' => $durations === [] ? 0 : min($durations),
                'max_duration_minutes' => $durations === [] ? 0 : max($durations),
            ],
            'duration_buckets' => [
                ['value' => 'under_6h', 'count' => $durationBuckets['under_6h']],
                ['value' => '6_12h', 'count' => $durationBuckets['6_12h']],
                ['value' => '12_20h', 'count' => $durationBuckets['12_20h']],
                ['value' => 'over_20h', 'count' => $durationBuckets['over_20h']],
            ],
            'layover_airports' => array_values(array_map(fn (string $code, int $count): array => ['code' => $code, 'name' => $code, 'count' => $count], array_keys($layovers), $layovers)),
            'fare_families' => array_values(array_map(fn (string $value, int $count): array => ['value' => $value, 'label' => ucwords(str_replace('_', ' ', $value)), 'count' => $count], array_keys($fareFamilies), $fareFamilies)),
            'bookable_status' => [
                ['value' => 'bookable', 'count' => $bookable],
                ['value' => 'price_unavailable', 'count' => $unavailable],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    protected function formatSearchSummary(array $criteria): string
    {
        $trip = (string) ($criteria['trip_type'] ?? 'one_way');
        if ($trip === 'multi_city' && ! empty($criteria['segments']) && is_array($criteria['segments'])) {
            $parts = [];
            foreach ($criteria['segments'] as $seg) {
                if (! is_array($seg)) {
                    continue;
                }
                $parts[] = ($seg['origin'] ?? '').' → '.($seg['destination'] ?? '').' · '.($seg['departure_date'] ?? '');
            }

            return implode(' · ', array_filter($parts));
        }

        $from = (string) ($criteria['origin'] ?? '');
        $to = (string) ($criteria['destination'] ?? '');
        $dep = (string) ($criteria['depart_date'] ?? '');
        try {
            $depLabel = $dep !== '' ? Carbon::parse($dep)->format('l, M j, Y') : '';
        } catch (\Throwable) {
            $depLabel = $dep;
        }

        if ($trip === 'round_trip') {
            $ret = (string) ($criteria['return_date'] ?? '');
            try {
                $retLabel = $ret !== '' ? Carbon::parse($ret)->format('l, M j, Y') : '';
            } catch (\Throwable) {
                $retLabel = $ret;
            }

            return trim($from.' ⇄ '.$to.' · '.$depLabel.($retLabel !== '' ? ' / '.$retLabel : ''));
        }

        return trim($from.' → '.$to.' · '.$depLabel);
    }

    protected function cabinMetaLabel(string $value): string
    {
        return match (strtolower($value)) {
            'premium_economy' => 'Premium Economy',
            'business' => 'Business',
            'first' => 'First',
            'economy' => 'Economy',
            default => ucfirst(str_replace('_', ' ', $value)),
        };
    }

    private function matchesTimeWindow(string $dateTime, string $window): bool
    {
        return $this->timeWindow($dateTime) === $window;
    }

    private function timeWindow(string $dateTime): ?string
    {
        if ($dateTime === '') {
            return null;
        }

        $hour = (int) date('G', strtotime($dateTime));

        return match (true) {
            $hour <= 5 => 'early_morning',
            $hour <= 11 => 'morning',
            $hour <= 17 => 'afternoon',
            default => 'evening',
        };
    }

    private function windowMeta(array $counts): array
    {
        $labels = [
            'early_morning' => 'Early morning (00:00-05:59)',
            'morning' => 'Morning (06:00-11:59)',
            'afternoon' => 'Afternoon (12:00-17:59)',
            'evening' => 'Evening (18:00-23:59)',
        ];

        $meta = [];
        foreach ($labels as $value => $label) {
            $meta[] = ['value' => $value, 'label' => $label, 'count' => (int) ($counts[$value] ?? 0)];
        }

        return $meta;
    }

    private function matchesDurationBucket(int $minutes, string $bucket): bool
    {
        return $this->durationBucket($minutes) === $bucket;
    }

    private function durationBucket(int $minutes): string
    {
        return match (true) {
            $minutes < 360 => 'under_6h',
            $minutes < 720 => '6_12h',
            $minutes < 1200 => '12_20h',
            default => 'over_20h',
        };
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return list<string>
     */
    private function layoverAirportsFromSegments(array $segments): array
    {
        $layovers = [];
        for ($i = 0; $i < count($segments) - 1; $i++) {
            $destination = strtoupper((string) ($segments[$i]['destination'] ?? ''));
            if ($destination !== '') {
                $layovers[] = $destination;
            }
        }

        return array_values(array_unique($layovers));
    }
}
