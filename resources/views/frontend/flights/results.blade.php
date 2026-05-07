@extends('layouts.frontend')

@section('title', 'Flight results')

@section('content')
    <div class="ota-results-pro">
        <div class="ota-results-pro-head">
            <div class="container">
                <div class="row">
                    <div class="col-sm-8">
                        <h1 class="ota-results-pro-title">Available flights</h1>
                        <p class="ota-results-pro-sub">
                            {{ $searchSummary ?? '' }}
                            <span class="label label-primary" style="margin-left:8px;">Fare options</span>
                        </p>
                        <p class="small text-muted" style="margin-top:8px;margin-bottom:0;">
                            All fares are shown in Pakistani Rupees (PKR). Past departure dates are not allowed.
                            Same-day flights must depart at least 10 hours from now. Book Now appears only when a PKR total is confirmed and the fare can be booked online.
                        </p>
                    </div>
                    <div class="col-sm-4 text-right hidden-xs">
                        <a href="{{ route('flights.search') }}" class="btn btn-default">Edit search</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="container ota-results-pro-body">
            <div class="row">
                <aside class="col-md-3 ota-results-filters" data-filter-panel>
                    <div class="ota-results-mobile-bar visible-xs visible-sm">
                        <button type="button" class="btn btn-default" data-mobile-open-sort aria-label="Open sort and filters">Sort &amp; filters</button>
                        <button type="button" class="btn btn-primary" data-mobile-filter-open>Filter results <span class="badge" data-active-filter-count>0</span></button>
                    </div>
                    <div class="ota-filter-backdrop visible-xs visible-sm" data-filter-backdrop aria-hidden="true"></div>
                    <div class="ota-filter-card" data-filter-drawer>
                        <div class="ota-filter-card-head">
                            <h4 class="ota-filter-title">Refine results</h4>
                            <button type="button" class="btn btn-link btn-sm visible-xs visible-sm" data-mobile-filter-close aria-label="Close">Close</button>
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
                <div class="col-md-9" data-results-root data-search-id="{{ $searchId }}">
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
                            <article class="ota-result-pro-card">
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
        var layover = offer.layover_summary ? '<p class="ota-detail-line"><strong>Layovers</strong> ' + esc(offer.layover_summary) + '</p>' : '';
        var seatsLeft = offer.seats_left != null && offer.seats_left !== '' ? '<p class="ota-detail-line"><strong>Seats left</strong> ' + esc(offer.seats_left) + '</p>' : '';
        var opLeg = (offer.operating_airline_code || '') ? '<p class="ota-detail-line"><strong>Operating airline</strong> ' + esc(offer.operating_airline_code) + '</p>' : '';

        var baggageBlock = '';
        if (offer.baggage_checked_display || offer.baggage_cabin_display || offer.baggage_summary_display || offer.baggage) {
            baggageBlock += '<div class="ota-detail-section"><h4 class="ota-detail-section-title">Baggage</h4>';
            if (offer.baggage_checked_display) {
                baggageBlock += '<p class="ota-detail-line"><strong>Checked</strong> ' + esc(offer.baggage_checked_display) + '</p>';
            }
            if (offer.baggage_cabin_display) {
                baggageBlock += '<p class="ota-detail-line"><strong>Cabin</strong> ' + esc(offer.baggage_cabin_display) + '</p>';
            }
            if (offer.baggage_summary_display || offer.baggage) {
                baggageBlock += '<p class="ota-detail-line"><strong>Summary</strong> ' + esc(offer.baggage_summary_display || offer.baggage) + '</p>';
            }
            baggageBlock += '</div>';
        } else {
            baggageBlock = '<div class="ota-detail-section"><h4 class="ota-detail-section-title">Baggage</h4><p class="ota-detail-line text-muted">Baggage information not provided for this fare.</p></div>';
        }

        var segHtml = '';
        (offer.segments || []).forEach(function (seg, idx) {
            var title = 'Segment ' + (idx + 1);
            segHtml += '<div class="ota-detail-segment-card"><div class="ota-detail-segment-head">' + esc(title) + '</div>';
            segHtml += '<p class="ota-detail-line">' + esc(seg.origin || '') + (seg.origin_city ? ' · ' + esc(seg.origin_city) : '') + ' → ' + esc(seg.destination || '') + (seg.destination_city ? ' · ' + esc(seg.destination_city) : '') + '</p>';
            segHtml += '<p class="ota-detail-line"><strong>Depart</strong> ' + esc(seg.departure_time_display || '') + ' · ' + esc(seg.departure_date_display || '') + '</p>';
            segHtml += '<p class="ota-detail-line"><strong>Arrive</strong> ' + esc(seg.arrival_time_display || '') + ' · ' + esc(seg.arrival_date_display || '') + '</p>';
            if (seg.duration_display) {
                segHtml += '<p class="ota-detail-line"><strong>Flight time</strong> ' + esc(seg.duration_display) + '</p>';
            }
            var fn = [esc(seg.airline_code || ''), esc(seg.flight_number || '')].filter(Boolean).join(' ');
            if (fn) {
                segHtml += '<p class="ota-detail-line"><strong>Flight</strong> ' + fn + (seg.airline_name ? ' (' + esc(seg.airline_name) + ')' : '') + '</p>';
            }
            if (seg.operating_airline_code || seg.operating_airline_name) {
                segHtml += '<p class="ota-detail-line"><strong>Operated by</strong> ' + esc([seg.operating_airline_code, seg.operating_airline_name].filter(Boolean).join(' · ')) + '</p>';
            }
            segHtml += '</div>';
        });

        var detailsInner =
            '<div class="ota-detail-section"><h4 class="ota-detail-section-title">Route</h4>' +
            '<p class="ota-detail-line"><strong>From</strong> ' + depCode + (depCity ? ' · ' + depCity : '') + '</p>' +
            '<p class="ota-detail-line"><strong>To</strong> ' + arrCode + (arrCity ? ' · ' + arrCity : '') + '</p>' +
            '<p class="ota-detail-line"><strong>Departure</strong> ' + depTime + ' · ' + depDate + '</p>' +
            '<p class="ota-detail-line"><strong>Arrival</strong> ' + arrTime + ' · ' + arrDate + '</p>' +
            '<p class="ota-detail-line"><strong>Duration</strong> ' + esc(offer.duration || '') + '</p>' +
            '<p class="ota-detail-line"><strong>Stops</strong> ' + esc(String(stopCount)) + '</p>' +
            layover +
            '</div>' +
            '<div class="ota-detail-section"><h4 class="ota-detail-section-title">Airline</h4>' +
            '<p class="ota-detail-line"><strong>Marketing carrier</strong> ' + esc(offer.airline_name || '') + ' (' + esc(offer.airline_code || '') + ')</p>' +
            ((offer.flight_number || '') ? '<p class="ota-detail-line"><strong>Flight number</strong> ' + esc(offer.flight_number) + '</p>' : '') +
            opLeg +
            '</div>' +
            '<div class="ota-detail-section"><h4 class="ota-detail-section-title">Cabin and fare</h4>' +
            '<p class="ota-detail-line"><strong>Cabin</strong> ' + esc(offer.cabin || '—') + '</p>' +
            '<p class="ota-detail-line"><strong>Fare family</strong> ' + esc(offer.fare_family || '—') + '</p>' +
            '<p class="ota-detail-line"><strong>Refundability</strong> ' + (offer.refundable ? 'Refundable' : 'Non-refundable') + '</p>' +
            seatsLeft +
            '</div>' +
            baggageBlock +
            (segHtml ? '<div class="ota-detail-section"><h4 class="ota-detail-section-title">Segments</h4>' + segHtml + '</div>' : '') +
            '<p class="ota-detail-pricing-note small text-muted">Final fare shown in PKR includes taxes, markup, and service fee.</p>';

        return '' +
            '<article class="ota-result-pro-card ota-result-card-v2"><div class="row ota-result-card-row">' +
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
            '<div class="ota-price-stack"><div class="ota-price-lg">' + esc(offer.price_display || 'PKR fare unavailable') + '</div><div class="ota-price-sub">' + esc(offer.price_note || '') + '</div></div>' +
            '<p class="ota-pay-later small text-muted">Continue to review fare and passenger details before submitting.</p>' +
            (offer.can_book
                ? '<a class="btn btn-primary btn-block ota-select-primary ota-btn-book" href="' + String(offer.select_url || '').replace(/"/g, '&quot;') + '">Book Now</a>'
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
            backdrop.style.display = 'block';
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    }
    function closeFilterDrawer() {
        if (!drawer) return;
        drawer.classList.remove('ota-filter-drawer--open');
        if (window.innerWidth < 992) drawer.style.display = 'none';
        document.body.style.overflow = '';
        if (backdrop) {
            backdrop.style.display = 'none';
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

    loadMore.addEventListener('click', function () { fetchPage(false); });
    fetchPage(true);
})();
</script>
@endpush
