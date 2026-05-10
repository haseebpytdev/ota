@extends('layouts.frontend')

@section('title', 'Flight results')

@section('content')
    @php
        $inlineDisplay = $inlineDisplay ?? [
            'origin_code' => strtoupper(trim((string) ($criteria['origin'] ?? ''))),
            'destination_code' => strtoupper(trim((string) ($criteria['destination'] ?? ''))),
            'origin_subtitle' => '',
            'destination_subtitle' => '',
            'depart_main' => '',
            'depart_day' => '',
            'return_main' => '',
            'return_day' => '',
        ];
    @endphp
    <div class="ota-results-pro">
        <header class="ota-results-pro-head" aria-labelledby="ota-results-heading">
            <div class="container">
                <div class="ota-results-pro-head-grid">
                    <div class="ota-results-pro-head-main">
                        <p class="ota-results-pro-kicker"><i class="fa fa-plane" aria-hidden="true"></i> Flight results</p>
                        <h1 id="ota-results-heading" class="ota-results-pro-title">Available flights</h1>
                        <p class="ota-results-pro-sub">
                            <span data-hero-route-summary>{{ $searchSummary ?? '' }}</span>
                            <span class="ota-results-pro-pill">Fares in PKR</span>
                        </p>
                    </div>
                </div>
            </div>
        </header>
        <div class="container ota-results-pro-body ota-results-pro-body--pullup ota-results-pro-body--wide">
            <div class="ota-results-widget-wide">
                @include('frontend.partials.ota-flight-widget', [
                    'variant' => 'standalone',
                    'show_intro' => false,
                    'defaultDepart' => $criteria['depart_date'] ?? '',
                    'defaultOrigin' => $criteria['origin'] ?? '',
                    'defaultDestination' => $criteria['destination'] ?? '',
                    'defaultReturnDate' => $criteria['return_date'] ?? '',
                    'defaultTripType' => $criteria['trip_type'] ?? 'one_way',
                    'minDate' => now()->format('Y-m-d'),
                ])
            </div>
            <div class="row">
                <aside class="col-md-3 ota-results-filters" data-filter-panel>
                    <div class="ota-results-mobile-bar">
                        <button type="button" class="btn btn-default" data-mobile-open-sort aria-label="Open sort and filters">Sort &amp; filters</button>
                        <button type="button" class="btn btn-primary" data-mobile-filter-open>Filter results <span class="badge" data-active-filter-count>0</span></button>
                    </div>
                    <div class="ota-filter-backdrop" data-filter-backdrop aria-hidden="true"></div>
                    <div class="ota-filter-card" data-filter-drawer>
                        <div class="ota-filter-card-head">
                            <h4 class="ota-filter-title">Refine results</h4>
                            <button type="button" class="btn btn-link btn-sm ota-filter-close-btn" data-mobile-filter-close aria-label="Close">Close</button>
                        </div>
                        <details class="ota-filter-section" open>
                            <summary class="ota-filter-section-title">Sort &amp; journey</summary>
                        <div class="form-group">
                            <label class="control-label">Sort</label>
                            <select class="form-control" data-filter-sort data-filter-key="sort" id="ota-filter-sort">
                                <option value="recommended">Recommended</option>
                                <option value="cheapest">Cheapest</option>
                                <option value="fastest">Fastest</option>
                                <option value="earliest_departure">Earliest departure</option>
                                <option value="latest_departure">Latest departure</option>
                                <option value="airline_az">Airline A–Z</option>
                                <option value="price_desc">Price: high to low</option>
                                <option value="arrival_time">Arrival time</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Stops</label>
                            <select class="form-control" data-filter-stops data-filter-key="stops">
                                <option value="">Any</option>
                                <option value="direct">Non-stop</option>
                                <option value="1_stop">1 stop</option>
                                <option value="2_plus">2+ stops</option>
                            </select>
                        </div>
                        </details>
                        <details class="ota-filter-section" open>
                            <summary class="ota-filter-section-title">Airline &amp; fare</summary>
                        <div class="form-group">
                            <label class="control-label">Airlines</label>
                            <select class="form-control" data-filter-airline data-filter-key="airline">
                                <option>All carriers</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Cabin</label>
                            <select class="form-control" data-filter-cabin data-filter-key="cabin"><option value="">Any</option></select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Baggage</label>
                            <select class="form-control" data-filter-baggage data-filter-key="baggage"><option value="">Any</option></select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Fare family</label>
                            <select class="form-control" data-filter-fare-family data-filter-key="fare_family"><option value="">Any</option></select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Refundable only</label>
                            <div class="checkbox"><label><input type="checkbox" data-filter-refundable data-filter-key="refundable"> Yes</label></div>
                        </div>
                        <div class="form-group checkbox">
                            <label><input type="checkbox" data-filter-bookable-only data-filter-key="bookable_only"> Bookable only</label>
                        </div>
                        </details>
                        <details class="ota-filter-section" open>
                            <summary class="ota-filter-section-title">Schedule &amp; connections</summary>
                        <div class="form-group">
                            <label class="control-label">Departure time</label>
                            <select class="form-control" data-filter-departure-window data-filter-key="departure_window"><option value="">Any</option></select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Arrival time</label>
                            <select class="form-control" data-filter-arrival-window data-filter-key="arrival_window"><option value="">Any</option></select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Duration bucket</label>
                            <select class="form-control" data-filter-duration-bucket data-filter-key="duration_bucket"><option value="">Any</option></select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Layover airport</label>
                            <select class="form-control" data-filter-layover-airport data-filter-key="layover_airport"><option value="">Any</option></select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Operating airline</label>
                            <select class="form-control" data-filter-operating-airline data-filter-key="operating_airline"><option value="">Any</option></select>
                        </div>
                        </details>
                        <div class="ota-filter-actions">
                            <button type="button" class="btn btn-default btn-block" data-filter-reset>Clear all filters</button>
                            <button type="button" class="btn btn-primary btn-block visible-xs visible-sm" data-mobile-filter-apply>Apply filters</button>
                        </div>
                        <p class="small text-muted ota-filter-hint">Filters apply to this search only — no new supplier search.</p>
                    </div>
                </aside>
                <div class="col-md-9" data-results-root data-flight-results data-search-id="{{ $searchId }}">
                        @if (!empty($warnings ?? []))
                        <div class="alert alert-warning">
                            <strong>Search notice:</strong>
                            <ul style="margin:8px 0 0 18px;">
                                @foreach ($warnings as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <p class="text-muted" data-results-summary>Showing fares...</p>
                    <div data-active-filter-chips style="margin-bottom:10px;"></div>
                    <div data-results-list>
                        @for ($i = 0; $i < 3; $i++)
                            <article class="ota-result-pro-card" data-flight-card>
                                <div class="row">
                                    <div class="col-xs-12">
                                        <div class="text-muted">Loading fares...</div>
                                    </div>
                                </div>
                            </article>
                        @endfor
                    </div>
                    <div class="text-center" style="margin-top:12px;">
                        <button type="button" class="btn btn-default" data-load-more disabled>Load more</button>
                    </div>
                    <p class="text-muted" data-expired-message style="display:none;margin-top:12px;">This fare search has expired. Please search again.</p>
                    <p class="text-muted" data-empty-filtered-message style="display:none;margin-top:12px;">No fares match your filters. Try clearing filters.</p>
                    <p style="margin-top: 16px;"><a href="{{ route('flights.search') }}">← Back to search</a> · <a href="{{ route('home') }}">Home</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-results-root]');
    if (!root) return;
    var searchId = root.getAttribute('data-search-id');
    if (!searchId) return;
    var list = root.querySelector('[data-results-list]');
    var loadMore = root.querySelector('[data-load-more]');
    var expired = root.querySelector('[data-expired-message]');
    var summary = root.querySelector('[data-results-summary]');
    var chips = root.querySelector('[data-active-filter-chips]');
    var filteredEmpty = root.querySelector('[data-empty-filtered-message]');
    var mobileOpenBtns = document.querySelectorAll('[data-mobile-filter-open]');
    var mobileApply = document.querySelector('[data-mobile-filter-apply]');
    var mobileCloseBtns = document.querySelectorAll('[data-mobile-filter-close]');
    var mobileOpenSort = document.querySelector('[data-mobile-open-sort]');
    var drawer = document.querySelector('[data-filter-drawer]');
    var backdrop = document.querySelector('[data-filter-backdrop]');
    var page = 1;
    var loading = false;
    var hasMore = true;
    var inlinePanel = document.querySelector('[data-inline-search]');
    var inlineForm = inlinePanel ? inlinePanel.querySelector('[data-inline-form]') : null;
    var inlineStatus = inlinePanel ? inlinePanel.querySelector('[data-inline-status]') : null;
    var inlineError = inlinePanel ? inlinePanel.querySelector('[data-inline-error]') : null;
    var heroRouteSummary = document.querySelector('[data-hero-route-summary]');
    var currentCriteria = @json($criteria ?? []);
    var currentFilters = {
        airline: '',
        stops: '',
        refundable: '',
        cabin: '',
        baggage: '',
        departure_window: '',
        arrival_window: '',
        duration_bucket: '',
        layover_airport: '',
        fare_family: '',
        bookable_only: '',
        operating_airline: '',
        sort: 'recommended'
    };
    var filterAirline = document.querySelector('[data-filter-airline]');
    var filterStops = document.querySelector('[data-filter-stops]');
    var filterRefundable = document.querySelector('[data-filter-refundable]');
    var filterSort = document.querySelector('[data-filter-sort]');
    var filterReset = document.querySelector('[data-filter-reset]');

    function esc(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }

    function cardHtml(offer) {
        var passengerMix = (currentCriteria.adults || 1) + ' adults, ' + (currentCriteria.children || 0) + ' child, ' + (currentCriteria.infants || 0) + ' infant';
        var cardPrice = offer.can_book
            ? ('Rs ' + esc(Math.round(Number(offer.final_customer_price || 0)).toLocaleString()) + ' PKR total')
            : esc(offer.price_display || 'PKR fare unavailable');
        var fareBreakdownCta = '<button class="btn btn-link btn-xs ota-fare-breakdown-link" type="button" data-toggle-details="' + detailsId + '">View fare breakdown</button>';
        function kv(label, value) {
            if (value === null || value === undefined || value === '') return '';
            return '<div class="ota-detail-kv"><span class="ota-detail-k">' + esc(label) + '</span><span class="ota-detail-v">' + esc(String(value)) + '</span></div>';
        }
        var logoHtml = offer.airline_logo_url
            ? '<div class="ota-result-brand-logo ota-airline-logo ota-airline-logo--img"><img src="' + esc(offer.airline_logo_url) + '" alt="' + esc(offer.airline_name || 'Airline') + ' logo" loading="lazy"></div>'
            : '<div class="ota-result-brand-logo ota-airline-logo">' + esc(offer.airline_code || 'XX') + '</div>';
        var refundable = offer.refundable
            ? '<span class="label label-success ota-result-badge">Refundable</span>'
            : '<span class="label label-warning ota-result-badge">Non-refundable</span>';
        var detailsId = 'offer-details-' + esc(offer.offer_id);
        var stopCount = offer.stops || 0;
        var stopsLabel = stopCount === 0 ? 'Non-stop' : (stopCount + ' stop' + (stopCount === 1 ? '' : 's'));
        var depTime = esc(offer.departure_time_display || offer.departure_time || '');
        var depDate = esc(offer.departure_date_display || '');
        var depCode = esc(offer.departure_airport_code || @json($criteria['origin'] ?? ''));
        var depCity = esc(offer.departure_city || '');
        var arrTime = esc(offer.arrival_time_display || offer.arrival_time || '');
        var arrDate = esc(offer.arrival_date_display || '');
        var arrCode = esc(offer.arrival_airport_code || @json($criteria['destination'] ?? ''));
        var arrCity = esc(offer.arrival_city || '');
        var arrOff = offer.arrival_day_offset ? '<span class="label label-default ota-arr-offset">' + esc(offer.arrival_day_offset) + '</span>' : '';
        var layoverNote = offer.layover_summary
            ? '<div class="ota-flight-detail-layover"><i class="fa fa-map-marker" aria-hidden="true"></i> ' + esc(offer.layover_summary) + '</div>'
            : '';
        var seatsKv = (offer.seats_left != null && offer.seats_left !== '')
            ? kv('Seats left', offer.seats_left)
            : '';
        var opAirKv = (offer.operating_airline_code || offer.operating_airline_name)
            ? kv('Operating airline', [offer.operating_airline_name, offer.operating_airline_code].filter(Boolean).join(' · '))
            : '';
        var airlineKvVal = '';
        if (offer.airline_name && offer.airline_code) {
            airlineKvVal = offer.airline_name + ' (' + offer.airline_code + ')';
        } else if (offer.airline_name) {
            airlineKvVal = offer.airline_name;
        } else if (offer.airline_code) {
            airlineKvVal = offer.airline_code;
        }
        var airlineKv = airlineKvVal ? kv('Airline', airlineKvVal) : '';

        var baggageBand = '';
        var bagSummaryRaw = offer.baggage_summary_display || offer.baggage || '';
        if (bagSummaryRaw) {
            baggageBand = '<div class="ota-flight-detail-band"><span class="ota-flight-detail-band__icon" aria-hidden="true"><i class="fa fa-suitcase"></i></span><div class="ota-flight-detail-band__body"><span class="ota-flight-detail-band__label">Baggage</span><span class="ota-flight-detail-band__value">' + esc(bagSummaryRaw) + '</span></div></div>';
        } else if (offer.baggage_checked_display || offer.baggage_cabin_display) {
            var bagParts = [];
            if (offer.baggage_checked_display) bagParts.push('Checked ' + esc(offer.baggage_checked_display));
            if (offer.baggage_cabin_display) bagParts.push('Cabin carry-on ' + esc(offer.baggage_cabin_display));
            baggageBand = '<div class="ota-flight-detail-band"><span class="ota-flight-detail-band__icon" aria-hidden="true"><i class="fa fa-suitcase"></i></span><div class="ota-flight-detail-band__body"><span class="ota-flight-detail-band__label">Baggage</span><span class="ota-flight-detail-band__value">' + bagParts.join(' · ') + '</span></div></div>';
        } else {
            baggageBand = '<div class="ota-flight-detail-band ota-flight-detail-band--muted"><span class="ota-flight-detail-band__icon" aria-hidden="true"><i class="fa fa-suitcase"></i></span><div class="ota-flight-detail-band__body"><span class="ota-flight-detail-band__value">Baggage not specified for this fare.</span></div></div>';
        }

        var segs = offer.segments || [];
        var legsHtml = '';
        if (segs.length > 1) {
            legsHtml = '<div class="ota-flight-detail-legs" role="list"><div class="ota-flight-detail-section-label">Itinerary</div>';
            segs.forEach(function (seg, idx) {
                var routeLine = esc(seg.origin || '') + (seg.origin_city ? ' · ' + esc(seg.origin_city) : '') + ' → ' + esc(seg.destination || '') + (seg.destination_city ? ' · ' + esc(seg.destination_city) : '');
                var fnBits = [seg.airline_code, seg.flight_number].filter(Boolean);
                var fnLine = fnBits.map(function (x) { return esc(x); }).join(' ');
                if (seg.airline_name) {
                    fnLine += (fnLine ? ' · ' : '') + esc(seg.airline_name);
                }
                var timeLine = esc(seg.departure_time_display || '') + ' – ' + esc(seg.arrival_time_display || '');
                if (seg.departure_date_display) {
                    timeLine += ' · ' + esc(seg.departure_date_display);
                }
                var subParts = [];
                if (seg.duration_display) subParts.push(esc(seg.duration_display));
                if (seg.operating_airline_code || seg.operating_airline_name) {
                    subParts.push('Op ' + esc([seg.operating_airline_code, seg.operating_airline_name].filter(Boolean).join(' ')));
                }
                legsHtml += '<div class="ota-detail-leg-row" role="listitem">' +
                    '<span class="ota-detail-leg-num">' + (idx + 1) + '</span>' +
                    '<div class="ota-detail-leg-body">' +
                    '<div class="ota-detail-leg-route">' + routeLine + '</div>' +
                    (fnLine ? '<div class="ota-detail-leg-flight">' + fnLine + '</div>' : '') +
                    '<div class="ota-detail-leg-times">' + timeLine + '</div>' +
                    (subParts.length ? '<div class="ota-detail-leg-sub">' + subParts.join(' · ') + '</div>' : '') +
                    '</div></div>';
            });
            legsHtml += '</div>';
        }

        var detailsInner =
            '<div class="ota-flight-detail-shell">' +
            '<div class="ota-flight-detail-card">' +
            '<div class="ota-flight-detail-route">' +
            '<div class="ota-flight-detail-leg ota-flight-detail-leg--dep">' +
            '<span class="ota-flight-detail-code">' + depCode + '</span>' +
            (depCity ? '<span class="ota-flight-detail-city">' + depCity + '</span>' : '') +
            '<span class="ota-flight-detail-time">' + depTime + '</span>' +
            '<div class="ota-flight-detail-date-row">' +
            '<span class="ota-flight-detail-date">' + depDate + '</span>' +
            '</div>' +
            '</div>' +
            '<div class="ota-flight-detail-mid">' +
            '<span class="ota-flight-detail-dur">' + esc(offer.duration || '') + '</span>' +
            '<span class="ota-flight-detail-stops">' + esc(stopsLabel) + '</span>' +
            '</div>' +
            '<div class="ota-flight-detail-leg ota-flight-detail-leg--arr">' +
            '<span class="ota-flight-detail-code">' + arrCode + '</span>' +
            (arrCity ? '<span class="ota-flight-detail-city">' + arrCity + '</span>' : '') +
            '<span class="ota-flight-detail-time">' + arrTime + '</span>' +
            '<div class="ota-flight-detail-date-row">' +
            '<span class="ota-flight-detail-date">' + arrDate + '</span>' +
            arrOff +
            '</div>' +
            '</div>' +
            '</div>' +
            layoverNote +
            '<div class="ota-flight-detail-grid">' +
            airlineKv +
            ((offer.flight_number || '') ? kv('Flight no.', offer.flight_number) : '') +
            opAirKv +
            kv('Cabin', offer.cabin || '—') +
            kv('Fare family', offer.fare_family || '—') +
            kv('Refund', offer.refundable ? 'Refundable' : 'Non-refundable') +
            seatsKv +
            '</div>' +
            baggageBand +
            legsHtml +
            '</div>' +
            '<p class="ota-detail-pricing-note small text-muted">Final fare shown in PKR includes taxes, markup, and service fee.</p>' +
            '</div>';

        var providerCode = String(offer.provider || '').toLowerCase();
        return '' +
            '<article class="ota-result-pro-card ota-result-card-v2" data-flight-card data-provider="' + esc(providerCode) + '"><div class="row ota-result-card-row">' +
            '<div class="col-sm-2 text-center ota-result-col-brand">' + logoHtml +
            '<div class="ota-airline-name">' + esc(offer.airline_name || '') + '</div>' +
            '<div class="ota-flight-no">' + esc(offer.airline_code || '') + '</div></div>' +
            '<div class="col-sm-7 ota-result-col-route"><div class="row ota-result-schedule">' +
            '<div class="col-xs-4"><div class="ota-result-leg">' +
            '<div class="ota-time-lg">' + depTime + '</div>' +
            '<div class="ota-result-leg__date">' + depDate + '</div>' +
            '<div class="ota-result-leg__code">' + depCode + '</div>' +
            (depCity ? '<div class="ota-result-leg__city small text-muted">' + depCity + '</div>' : '') +
            '</div></div>' +
            '<div class="col-xs-4 text-center ota-result-mid"><div class="ota-dur-line">' + esc(offer.duration || '') + '</div><div class="ota-dur-bar"></div><span class="label label-default ota-result-stops">' + stopsLabel + '</span></div>' +
            '<div class="col-xs-4 text-right"><div class="ota-result-leg ota-result-leg--right">' +
            '<div class="ota-time-lg">' + arrTime + '</div>' +
            '<div class="ota-result-leg__date">' + arrDate + ' ' + arrOff + '</div>' +
            '<div class="ota-result-leg__code">' + arrCode + '</div>' +
            (arrCity ? '<div class="ota-result-leg__city small text-muted">' + arrCity + '</div>' : '') +
            '</div></div>' +
            '</div><div class="ota-result-tags"><span><i class="fa fa-suitcase"></i> ' + esc(offer.baggage_summary_display || offer.baggage || '') + '</span><span><i class="fa fa-ticket"></i> ' + esc(offer.cabin || 'Economy') + '</span><span><i class="fa fa-shield"></i> ' + (offer.refundable ? 'Refundable' : 'Non-refundable') + '</span></div></div>' +
            '<div class="col-sm-3 ota-result-col-price">' +
            '<div class="ota-result-badges">' + refundable + '</div>' +
            '<div class="ota-price-stack"><div class="ota-price-lg">' + cardPrice + '</div><div class="ota-price-sub">' + esc(passengerMix) + '</div></div>' +
            fareBreakdownCta +
            (offer.can_book
                ? '<a class="btn btn-primary btn-block ota-select-primary ota-btn-book" data-book-now data-provider="' + esc(providerCode) + '" href="' + String(offer.select_url || '').replace(/"/g, '&quot;') + '">Book Now</a>'
                : '<p class="small text-muted ota-result-disabled-msg">' + esc(offer.disabled_reason || 'This fare cannot be booked online.') + '</p><button type="button" class="btn btn-default btn-block" disabled>Not available to book</button>') +
            '<button class="btn btn-default btn-block ota-btn-details" type="button" data-toggle-details="' + detailsId + '">Flight details <span aria-hidden="true">▼</span></button>' +
            '</div>' +
            '</div><div class="ota-result-details-wrap"><div id="' + detailsId + '" class="ota-result-details" style="display:none;" hidden>' +
            detailsInner +
            '</div></div></article>';
    }

    function renderNoFares() {
        if (list) {
            list.innerHTML = '<p>No fares found for this route/date. Try a different date or contact support.</p>';
        }
    }

    function syncFilterControls(meta) {
        if (!meta) return;
        function fillSelect(selector, base, rows, valueKey, labelBuilder) {
            var node = document.querySelector(selector);
            if (!node) return;
            var opts = [base];
            (rows || []).forEach(function (row) {
                var v = row[valueKey];
                if (!v) return;
                opts.push('<option value="' + v + '">' + labelBuilder(row) + '</option>');
            });
            node.innerHTML = opts.join('');
            var key = node.getAttribute('data-filter-key');
            if (key && currentFilters[key] !== undefined) node.value = currentFilters[key] || '';
        }
        fillSelect('[data-filter-airline]', '<option value="">All carriers</option>', meta.airlines || [], 'code', function (a) { return (a.name || a.code) + ' (' + a.count + ')'; });
        fillSelect('[data-filter-cabin]', '<option value="">Any</option>', meta.cabin_classes || [], 'value', function (r) { return r.label + ' (' + r.count + ')'; });
        fillSelect('[data-filter-baggage]', '<option value="">Any</option>', meta.baggage_options || [], 'value', function (r) { return r.label + ' (' + r.count + ')'; });
        fillSelect('[data-filter-departure-window]', '<option value="">Any</option>', meta.departure_time_windows || [], 'value', function (r) { return r.label + ' (' + r.count + ')'; });
        fillSelect('[data-filter-arrival-window]', '<option value="">Any</option>', meta.arrival_time_windows || [], 'value', function (r) { return r.label + ' (' + r.count + ')'; });
        fillSelect('[data-filter-duration-bucket]', '<option value="">Any</option>', meta.duration_buckets || [], 'value', function (r) { return r.value + ' (' + r.count + ')'; });
        fillSelect('[data-filter-fare-family]', '<option value="">Any</option>', meta.fare_families || [], 'value', function (r) { return r.label + ' (' + r.count + ')'; });
        fillSelect('[data-filter-layover-airport]', '<option value="">Any</option>', meta.layover_airports || [], 'code', function (r) { return (r.name || r.code) + ' (' + r.count + ')'; });
        fillSelect('[data-filter-operating-airline]', '<option value="">Any</option>', meta.operating_airlines || [], 'code', function (r) { return (r.label || r.code) + ' (' + r.count + ')'; });
    }

    function queryString(pageNo) {
        var params = [
            'search_id=' + encodeURIComponent(searchId),
            'page=' + pageNo,
            'per_page=12',
            'sort=' + encodeURIComponent(currentFilters.sort || 'recommended')
        ];
        if (currentFilters.airline) params.push('airline=' + encodeURIComponent(currentFilters.airline));
        if (currentFilters.stops) params.push('stops=' + encodeURIComponent(currentFilters.stops));
        if (currentFilters.refundable) params.push('refundable=' + encodeURIComponent(currentFilters.refundable));
        if (currentFilters.cabin) params.push('cabin=' + encodeURIComponent(currentFilters.cabin));
        if (currentFilters.baggage) params.push('baggage=' + encodeURIComponent(currentFilters.baggage));
        if (currentFilters.departure_window) params.push('departure_window=' + encodeURIComponent(currentFilters.departure_window));
        if (currentFilters.arrival_window) params.push('arrival_window=' + encodeURIComponent(currentFilters.arrival_window));
        if (currentFilters.duration_bucket) params.push('duration_bucket=' + encodeURIComponent(currentFilters.duration_bucket));
        if (currentFilters.layover_airport) params.push('layover_airport=' + encodeURIComponent(currentFilters.layover_airport));
        if (currentFilters.fare_family) params.push('fare_family=' + encodeURIComponent(currentFilters.fare_family));
        if (currentFilters.bookable_only) params.push('bookable_only=' + encodeURIComponent(currentFilters.bookable_only));
        if (currentFilters.operating_airline) params.push('operating_airline=' + encodeURIComponent(currentFilters.operating_airline));
        return params.join('&');
    }

    function updateResultsUrl() {
        var params = new URLSearchParams();
        params.set('trip_type', currentCriteria.trip_type || 'one_way');
        params.set('from', currentCriteria.origin || '');
        params.set('to', currentCriteria.destination || '');
        params.set('depart', currentCriteria.depart_date || '');
        if ((currentCriteria.trip_type || 'one_way') === 'round_trip' && currentCriteria.return_date) {
            params.set('return_date', currentCriteria.return_date);
        }
        params.set('cabin', currentCriteria.cabin || 'economy');
        params.set('adults', String(currentCriteria.adults || 1));
        params.set('children', String(currentCriteria.children || 0));
        params.set('infants', String(currentCriteria.infants || 0));
        window.history.pushState({}, '', '/flights/results?' + params.toString());
    }

    function resetFiltersForNewSearch() {
        currentFilters = {airline: '', stops: '', refundable: '', cabin: '', baggage: '', departure_window: '', arrival_window: '', duration_bucket: '', layover_airport: '', fare_family: '', bookable_only: '', operating_airline: '', sort: 'recommended'};
        Array.prototype.forEach.call(document.querySelectorAll('[data-filter-key]'), function (node) {
            if (node.type === 'checkbox') node.checked = false;
            else node.value = (node.getAttribute('data-filter-key') === 'sort' ? 'recommended' : '');
        });
    }

    function renderChips() {
        if (!chips) return;
        var parts = [];
        Object.keys(currentFilters).forEach(function (key) {
            if (!currentFilters[key] || key === 'sort') return;
            parts.push('<button type="button" class="btn btn-default btn-xs ota-filter-chip" data-chip-remove="' + key + '">' + currentFilters[key] + ' ×</button>');
        });
        chips.innerHTML = parts.length ? '<span class="ota-filter-chips-label">Active:</span> ' + parts.join(' ') : '';
        Array.prototype.forEach.call(chips.querySelectorAll('[data-chip-remove]'), function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.getAttribute('data-chip-remove');
                currentFilters[key] = '';
                var control = document.querySelector('[data-filter-key="' + key + '"]');
                if (control) {
                    if (control.type === 'checkbox') control.checked = false;
                    else control.value = '';
                }
                applyFilters();
            });
        });
        document.querySelectorAll('[data-active-filter-count]').forEach(function (n) {
            n.textContent = String(parts.length);
        });
    }

    function bindDetailsToggles() {
        var toggles = list.querySelectorAll('[data-toggle-details]');
        Array.prototype.forEach.call(toggles, function (btn) {
            if (btn.getAttribute('data-bound') === '1') return;
            btn.setAttribute('data-bound', '1');
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-toggle-details');
                var block = document.getElementById(id);
                if (!block) return;
                var expanded = !block.hasAttribute('hidden');
                if (expanded) {
                    block.style.display = 'none';
                    block.setAttribute('hidden', 'hidden');
                } else {
                    block.style.display = 'block';
                    block.removeAttribute('hidden');
                }
                btn.innerHTML = expanded
                    ? 'Flight details <span aria-hidden="true">▼</span>'
                    : 'Hide details <span aria-hidden="true">▲</span>';
            });
        });
    }

    function fetchPage(reset) {
        if (loading || !hasMore) return;
        loading = true;
        loadMore.disabled = true;
        var targetPage = reset ? 1 : page;
        fetch('/flights/results/data?' + queryString(targetPage), {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (res) {
            if (res.status === 410) {
                expired.style.display = '';
                hasMore = false;
                throw new Error('expired');
            }
            return res.json();
        }).then(function (json) {
            if (reset) list.innerHTML = '';
            if (!json.offers || !json.offers.length) {
                if (targetPage === 1) renderNoFares();
                if (filteredEmpty) filteredEmpty.style.display = '';
                hasMore = false;
                return;
            }
            if (filteredEmpty) filteredEmpty.style.display = 'none';
            var html = json.offers.map(cardHtml).join('');
            list.insertAdjacentHTML('beforeend', html);
            bindDetailsToggles();
            if (targetPage === 1) {
                syncFilterControls(json.filters || null);
                renderChips();
            }
            if (summary) {
                summary.textContent = page === 1
                    ? ('Showing ' + (json.offers.length || 0) + ' of ' + (json.total || 0) + ' fares')
                    : ('Showing fares');
            }
            hasMore = !!json.has_more;
            if (hasMore) page = targetPage + 1;
        }).catch(function () {
        }).finally(function () {
            loading = false;
            loadMore.disabled = !hasMore;
        });
    }

    function applyFilters() {
        page = 1;
        hasMore = true;
        fetchPage(true);
    }

    function bindInlineAutocomplete() {
        if (!inlineForm) return;
        var timers = new WeakMap();
        var aborters = new WeakMap();
        function close(box) {
            if (box) {
                box.style.display = 'none';
                box.innerHTML = '';
            }
        }
        Array.prototype.forEach.call(inlineForm.querySelectorAll('.js-inline-airport'), function (input) {
            var cell = input.closest('[data-inline-airport-field]');
            var box = cell ? cell.querySelector('.ota-airport-suggest') : null;
            if (!box || input.getAttribute('data-bound') === '1') return;
            input.setAttribute('data-bound', '1');
            input.addEventListener('input', function () {
                var hidden = document.getElementById(input.getAttribute('data-hidden-target'));
                if (hidden) hidden.value = '';
                var sub = cell ? cell.querySelector('[data-inline-airport-sub]') : null;
                if (sub && !(input.value || '').trim()) sub.textContent = '';
                var oldTimer = timers.get(input);
                if (oldTimer) clearTimeout(oldTimer);
                timers.set(input, setTimeout(function () {
                    var q = (input.value || '').trim();
                    if (q.length < 2) return close(box);
                    var oldCtrl = aborters.get(input);
                    if (oldCtrl) oldCtrl.abort();
                    var ctrl = new AbortController();
                    aborters.set(input, ctrl);
                    fetch('/airports/search?q=' + encodeURIComponent(q) + '&limit=10', { signal: ctrl.signal, headers: {'X-Requested-With': 'XMLHttpRequest'} })
                        .then(function (r) { return r.ok ? r.json() : []; })
                        .then(function (rows) {
                            box.innerHTML = '';
                            (rows || []).forEach(function (row) {
                                var btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'ota-airport-item';
                                btn.innerHTML = '<span class="ota-airport-item-code">' + esc(row.iata || '') + '</span><span class="ota-airport-item-main">' + esc(row.label || '') + '</span><span class="ota-airport-item-sub">' + esc(row.description || '') + '</span>';
                                btn.addEventListener('pointerdown', function (event) {
                                    event.preventDefault();
                                    input.value = row.label || (row.iata || '');
                                    var hidden = document.getElementById(input.getAttribute('data-hidden-target'));
                                    if (hidden) hidden.value = row.iata || '';
                                    var subEl = cell ? cell.querySelector('[data-inline-airport-sub]') : null;
                                    if (subEl) {
                                        var c = (row.city || '').trim();
                                        var co = (row.country || '').trim();
                                        subEl.textContent = (c && co) ? (c + ', ' + co) : (c || co || '');
                                    }
                                    close(box);
                                });
                                box.appendChild(btn);
                            });
                            box.style.display = box.children.length ? 'block' : 'none';
                        })
                        .catch(function () {});
                }, 180));
            });
            input.addEventListener('blur', function () { setTimeout(function () { close(box); }, 100); });
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-filter-key]'), function (node) {
        node.addEventListener('change', function () {
            var key = node.getAttribute('data-filter-key');
            if (!key) return;
            currentFilters[key] = node.type === 'checkbox' ? (node.checked ? '1' : '') : (node.value || '');
            if (window.innerWidth < 992 && key !== 'sort') return;
            applyFilters();
        });
    });
    if (filterReset) filterReset.addEventListener('click', function () {
        currentFilters = {airline: '', stops: '', refundable: '', cabin: '', baggage: '', departure_window: '', arrival_window: '', duration_bucket: '', layover_airport: '', fare_family: '', bookable_only: '', operating_airline: '', sort: 'recommended'};
        Array.prototype.forEach.call(document.querySelectorAll('[data-filter-key]'), function (node) {
            if (node.type === 'checkbox') node.checked = false;
            else node.value = (node.getAttribute('data-filter-key') === 'sort' ? 'recommended' : '');
        });
        applyFilters();
    });
    function openFilterDrawer() {
        if (!drawer) return;
        drawer.classList.add('ota-filter-drawer--open');
        drawer.style.display = 'block';
        if (backdrop && window.innerWidth < 992) {
            backdrop.classList.add('is-open');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ota-filter-open');
        }
    }
    function closeFilterDrawer() {
        if (!drawer) return;
        drawer.classList.remove('ota-filter-drawer--open');
        if (window.innerWidth < 992) drawer.style.display = 'none';
        document.body.classList.remove('ota-filter-open');
        if (backdrop) {
            backdrop.classList.remove('is-open');
            backdrop.setAttribute('aria-hidden', 'true');
        }
    }
    mobileOpenBtns.forEach(function (btn) {
        btn.addEventListener('click', function () { if (window.innerWidth < 992) openFilterDrawer(); });
    });
    mobileCloseBtns.forEach(function (btn) {
        btn.addEventListener('click', function () { closeFilterDrawer(); });
    });
    if (backdrop) backdrop.addEventListener('click', function () { closeFilterDrawer(); });
    if (mobileApply && drawer) mobileApply.addEventListener('click', function () { applyFilters(); closeFilterDrawer(); });
    if (mobileOpenSort && drawer) {
        mobileOpenSort.addEventListener('click', function () {
            if (window.innerWidth >= 992) return;
            openFilterDrawer();
            var sortEl = document.getElementById('ota-filter-sort');
            if (sortEl) setTimeout(function () { sortEl.focus(); }, 50);
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeFilterDrawer();
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            closeFilterDrawer();
            drawer.style.display = 'block';
        } else {
            drawer.style.display = 'none';
        }
    });

    loadMore.addEventListener('click', function () { fetchPage(false); });
    var inlineTripType = inlineForm ? inlineForm.querySelector('[data-inline-trip-type]') : null;
    var tripChoices = inlineForm ? inlineForm.querySelectorAll('[data-trip-choice]') : [];
    var inlineDepart = inlineForm ? inlineForm.querySelector('[data-inline-depart-input]') : null;
    var inlineReturn = inlineForm ? inlineForm.querySelector('[data-inline-return-input]') : null;
    function parseYmdParts(iso) {
        if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) {
            return { main: '', day: '' };
        }
        var p = iso.split('-');
        var y = parseInt(p[0], 10);
        var m = parseInt(p[1], 10) - 1;
        var d = parseInt(p[2], 10);
        var dt = new Date(y, m, d);
        if (isNaN(dt.getTime())) {
            return { main: '', day: '' };
        }
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return {
            main: months[m] + ' ' + d + ', ' + y,
            day: dt.toLocaleDateString(undefined, { weekday: 'long' })
        };
    }
    function syncInlineDateLabels() {
        if (!inlineForm) return;
        var dm = inlineForm.querySelector('[data-inline-depart-main]');
        var dd = inlineForm.querySelector('[data-inline-depart-day]');
        if (inlineDepart && dm && dd) {
            var o = parseYmdParts(inlineDepart.value);
            dm.textContent = o.main;
            dd.textContent = o.day;
        }
        var rm = inlineForm.querySelector('[data-inline-return-main]');
        var rd = inlineForm.querySelector('[data-inline-return-day]');
        var rw = inlineForm.querySelector('[data-inline-return-wrap]');
        if (inlineReturn && rm && rd) {
            if (rw && rw.style.display !== 'none' && inlineReturn.value) {
                var r = parseYmdParts(inlineReturn.value);
                rm.textContent = r.main;
                rd.textContent = r.day;
            } else {
                rm.textContent = '';
                rd.textContent = '';
            }
        }
    }
    function applyInlineDisplayFromServer(d) {
        if (!d || !inlineForm) return;
        var subs = inlineForm.querySelectorAll('[data-inline-airport-sub]');
        if (subs[0] && d.origin_subtitle != null) subs[0].textContent = d.origin_subtitle;
        if (subs[1] && d.destination_subtitle != null) subs[1].textContent = d.destination_subtitle;
        var dm = inlineForm.querySelector('[data-inline-depart-main]');
        var dd = inlineForm.querySelector('[data-inline-depart-day]');
        if (dm && d.depart_main != null) dm.textContent = d.depart_main;
        if (dd && d.depart_day != null) dd.textContent = d.depart_day;
        var rm = inlineForm.querySelector('[data-inline-return-main]');
        var rd = inlineForm.querySelector('[data-inline-return-day]');
        if (rm && d.return_main != null) rm.textContent = d.return_main;
        if (rd && d.return_day != null) rd.textContent = d.return_day;
    }
    function syncInlineReturnMin() {
        if (!inlineDepart || !inlineReturn) return;
        var d = inlineDepart.value || '';
        if (d) {
            inlineReturn.min = d;
            if (inlineReturn.value && inlineReturn.value < d) inlineReturn.value = d;
        }
    }
    if (inlineTripType && inlineForm) {
        Array.prototype.forEach.call(tripChoices, function (btn) {
            btn.addEventListener('click', function () {
                var val = btn.getAttribute('data-trip-choice');
                if (!val || btn.disabled) return;
                inlineTripType.value = val;
                inlineForm.setAttribute('data-trip-mode', val);
                Array.prototype.forEach.call(tripChoices, function (b) { b.classList.toggle('is-active', b === btn); });
                inlineTripType.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
        inlineTripType.addEventListener('change', function () {
            inlineForm.setAttribute('data-trip-mode', inlineTripType.value || 'one_way');
            var wrap = inlineForm.querySelector('[data-inline-return-wrap]');
            if (wrap) wrap.style.display = inlineTripType.value === 'round_trip' ? '' : 'none';
            if (inlineTripType.value === 'round_trip') syncInlineReturnMin();
            syncInlineDateLabels();
        });
    }
    var swapBtn = inlineForm ? inlineForm.querySelector('[data-inline-swap]') : null;
    if (swapBtn && inlineForm) {
        swapBtn.addEventListener('click', function () {
            var fromDisplay = inlineForm.querySelector('#inline-from-display');
            var toDisplay = inlineForm.querySelector('#inline-to-display');
            var fromCode = inlineForm.querySelector('#inline-from');
            var toCode = inlineForm.querySelector('#inline-to');
            var subs = inlineForm.querySelectorAll('[data-inline-airport-sub]');
            if (!fromDisplay || !toDisplay || !fromCode || !toCode) return;
            var tmp = fromDisplay.value; fromDisplay.value = toDisplay.value; toDisplay.value = tmp;
            tmp = fromCode.value; fromCode.value = toCode.value; toCode.value = tmp;
            if (subs[0] && subs[1]) {
                tmp = subs[0].textContent; subs[0].textContent = subs[1].textContent; subs[1].textContent = tmp;
            }
        });
    }
    if (inlineDepart) {
        inlineDepart.addEventListener('change', function () {
            syncInlineReturnMin();
            syncInlineDateLabels();
        });
        inlineDepart.addEventListener('input', syncInlineDateLabels);
    }
    if (inlineReturn) {
        inlineReturn.addEventListener('change', syncInlineDateLabels);
        inlineReturn.addEventListener('input', syncInlineDateLabels);
    }
    syncInlineReturnMin();
    syncInlineDateLabels();
    bindInlineAutocomplete();
    if (inlineForm) {
        inlineForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (inlineError) inlineError.style.display = 'none';
            var fromHidden = inlineForm.querySelector('input[name="from"]');
            var toHidden = inlineForm.querySelector('input[name="to"]');
            var fromDisplay = inlineForm.querySelector('input[name="from_display"]');
            var toDisplay = inlineForm.querySelector('input[name="to_display"]');
            if (fromDisplay && fromDisplay.value.trim() && (!fromHidden || !fromHidden.value.trim())) {
                if (inlineError) { inlineError.textContent = 'Please select a valid origin airport from the dropdown.'; inlineError.style.display = ''; }
                return;
            }
            if (toDisplay && toDisplay.value.trim() && (!toHidden || !toHidden.value.trim())) {
                if (inlineError) { inlineError.textContent = 'Please select a valid destination airport from the dropdown.'; inlineError.style.display = ''; }
                return;
            }
            if (fromHidden && toHidden && fromHidden.value && fromHidden.value === toHidden.value) {
                if (inlineError) { inlineError.textContent = 'Origin and destination cannot be the same.'; inlineError.style.display = ''; }
                return;
            }
            var submitBtn = inlineForm.querySelector('[data-inline-submit]');
            if (submitBtn) submitBtn.disabled = true;
            if (inlineStatus) inlineStatus.textContent = 'Searching fares...';
            var params = new URLSearchParams(new FormData(inlineForm));
            fetch('/flights/results/search?' + params.toString(), { headers: {'X-Requested-With': 'XMLHttpRequest'} })
                .then(function (res) { return res.ok ? res.json() : res.json().then(function (e) { throw e; }); })
                .then(function (json) {
                    searchId = json.search_id;
                    root.setAttribute('data-search-id', searchId);
                    currentCriteria = json.criteria || {};
                    resetFiltersForNewSearch();
                    page = 1;
                    hasMore = true;
                    if (summary) summary.textContent = 'Showing fares...';
                    if (heroRouteSummary && json.summary && json.summary.text) heroRouteSummary.textContent = json.summary.text;
                    if (json.inline_display) applyInlineDisplayFromServer(json.inline_display);
                    syncInlineDateLabels();
                    updateResultsUrl();
                    fetchPage(true);
                })
                .catch(function (err) {
                    if (inlineError) {
                        inlineError.textContent = (err && err.message) ? err.message : 'Unable to refresh search. Please try again.';
                        inlineError.style.display = '';
                    }
                })
                .finally(function () {
                    if (submitBtn) submitBtn.disabled = false;
                    if (inlineStatus) inlineStatus.textContent = '';
                });
        });
    }
    fetchPage(true);
    closeFilterDrawer();
})();
</script>
@endpush
