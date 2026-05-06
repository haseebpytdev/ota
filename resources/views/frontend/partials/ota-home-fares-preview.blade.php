@php
    $offers = $homeFlightOffers ?? [];
    $q = $resultsQuery ?? ['from' => 'LHE', 'to' => 'DXB', 'depart' => now()->addDays(14)->format('Y-m-d')];
    $resultsUrl = route('flights.results', $q);
@endphp
<section class="ota-section ota-home-fares" id="fares">
    <div class="ota-container">
        <header class="ota-section-head ota-section-head--compact">
            <p class="ota-section-kicker">Inventory preview</p>
            <h2 class="ota-section-title">Available fares on your corridor</h2>
            <p class="ota-section-desc">Three current offers for <strong>{{ $q['from'] }} → {{ $q['to'] }}</strong>. Open full results to review more options.</p>
        </header>
        <div class="fare-grid">
            @foreach ($offers as $offer)
                <article class="fare-card">
                    <div class="fare-route">{{ $q['from'] }} → {{ $q['to'] }}</div>
                    <div class="fare-airline">
                        <span class="fare-code">{{ $offer['airline_code'] ?? ($offer['carrier_code'] ?? '') }}</span>
                        <span class="fare-airline-name">{{ $offer['airline_name'] ?? '' }}</span>
                    </div>
                    <div class="fare-meta">
                        <span class="fare-bag"><i class="fa fa-suitcase" aria-hidden="true"></i> {{ $offer['baggage'] ?? '' }}</span>
                        @if (!empty($offer['refundable']))
                            <span class="ota-pill ota-pill-ok">Refundable</span>
                        @else
                            <span class="ota-pill ota-pill-warn">Non-refundable</span>
                        @endif
                    </div>
                    <div class="fare-price">Rs {{ number_format((float) ($offer['total'] ?? 0), 0) }}</div>
                    <p class="fare-note">Includes taxes, markup, and service fee.</p>
                    <a href="{{ $resultsUrl }}" class="ota-btn ota-btn-primary fare-btn">Select flight</a>
                </article>
            @endforeach
        </div>
        <p class="fares-more">
            <a href="{{ $resultsUrl }}" class="fares-more-link">View all results for this route →</a>
        </p>
    </div>
</section>
