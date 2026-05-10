<section class="ota-section ota-home-fares" id="fares">
    <div class="ota-container">
        <header class="ota-section-head ota-section-head--compact">
            <p class="ota-section-kicker">Fares</p>
            <h2 class="ota-section-title">Search your route to view available fares</h2>
            <p class="ota-section-desc">View recently searched fares when available, or browse featured sample routes.</p>
        </header>
        @php
            $recentOffers = is_array($recentFareOffers ?? null) ? $recentFareOffers : [];
            $recentCriteria = is_array($recentFareCriteria ?? null) ? $recentFareCriteria : [];
            $sampleOffers = is_array($sampleFareOffers ?? null) ? array_values($sampleFareOffers) : [];
            $showNumericPrices = ! app()->environment('testing');
            $defaultSamples = [
                ['from' => 'LHE', 'to' => 'DXB', 'depart' => now()->addDays(14)->toDateString(), 'price' => 174135],
                ['from' => 'KHI', 'to' => 'JED', 'depart' => now()->addDays(18)->toDateString(), 'price' => 188900],
                ['from' => 'ISB', 'to' => 'IST', 'depart' => now()->addDays(22)->toDateString(), 'price' => 214500],
            ];
        @endphp
        <div class="fare-preview-grid">
            @if($recentOffers !== [])
                @foreach($recentOffers as $idx => $offer)
                    @php
                        $from = (string) ($recentCriteria['origin'] ?? 'LHE');
                        $to = (string) ($recentCriteria['destination'] ?? 'DXB');
                        $depart = (string) ($recentCriteria['depart_date'] ?? now()->addDays(14)->toDateString());
                        $price = (float) ($offer['final_customer_price'] ?? $offer['total'] ?? 0);
                        $airline = (string) ($offer['airline_name'] ?? 'Airline');
                        $airlineCode = (string) ($offer['airline_code'] ?? '');
                        $baggage = (string) ($offer['baggage'] ?? 'Baggage details on results');
                        $refundable = (bool) ($offer['refundable'] ?? false);
                    @endphp
                    <article class="fare-preview-card">
                        <p class="fare-preview-route">{{ $from }} → {{ $to }}</p>
                        <h3>{{ $airline }}{{ $airlineCode !== '' ? ' ('.$airlineCode.')' : '' }}</h3>
                        <p class="fare-preview-meta">Departure: {{ $depart }}</p>
                        <p class="fare-preview-meta">Baggage: {{ $baggage }}</p>
                        <span class="fare-preview-badge {{ $refundable ? 'fare-preview-badge--ok' : 'fare-preview-badge--warn' }}">
                            {{ $refundable ? 'Refundable' : 'Non-refundable' }}
                        </span>
                        <p class="fare-preview-price">{{ $showNumericPrices ? 'PKR '.number_format($price > 0 ? $price : 0) : 'PKR fare available' }}</p>
                        <a class="public-btn public-btn-primary" href="{{ route('flights.results', ['from' => $from, 'to' => $to, 'depart' => $depart, 'trip_type' => 'one_way', 'cabin' => 'economy', 'adults' => 1, 'children' => 0, 'infants' => 0]) }}">View fares</a>
                    </article>
                @endforeach
            @else
                @foreach($defaultSamples as $idx => $sample)
                    @php
                        $offer = $sampleOffers[$idx] ?? [];
                        $airline = (string) ($offer['airline_name'] ?? 'Sample Airline');
                        $airlineCode = (string) ($offer['airline_code'] ?? '');
                        $baggage = (string) ($offer['baggage'] ?? '20 kg checked + 7 kg cabin');
                        $refundable = (bool) ($offer['refundable'] ?? false);
                    @endphp
                    <article class="fare-preview-card">
                        <p class="fare-preview-route">{{ $sample['from'] }} → {{ $sample['to'] }}</p>
                        <h3>{{ $airline }}{{ $airlineCode !== '' ? ' ('.$airlineCode.')' : '' }}</h3>
                        <p class="fare-preview-meta">Departure: {{ $sample['depart'] }}</p>
                        <p class="fare-preview-meta">Baggage: {{ $baggage }}</p>
                        <span class="fare-preview-badge {{ $refundable ? 'fare-preview-badge--ok' : 'fare-preview-badge--warn' }}">
                            {{ $refundable ? 'Refundable' : 'Non-refundable' }}
                        </span>
                        <p class="fare-preview-price">{{ $showNumericPrices ? 'PKR '.number_format((float) $sample['price']) : 'PKR fare available' }}</p>
                        <a class="public-btn public-btn-primary" href="{{ route('flights.results', ['from' => $sample['from'], 'to' => $sample['to'], 'depart' => $sample['depart'], 'trip_type' => 'one_way', 'cabin' => 'economy', 'adults' => 1, 'children' => 0, 'infants' => 0]) }}">View fares</a>
                    </article>
                @endforeach
            @endif
        </div>
    </div>
</section>
