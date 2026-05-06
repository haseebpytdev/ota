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
                    <button type="button" class="btn btn-default btn-block visible-xs" data-mobile-filter-open>Filter results (<span data-active-filter-count>0</span>)</button>
                    <div class="ota-filter-card" data-filter-drawer>
                        <h4>Filters</h4>
                        <div class="form-group">
                            <label class="control-label">Sort</label>
                            <select class="form-control" data-filter-sort data-filter-key="sort">
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
                            <label class="control-label">Fare family</label>
                            <select class="form-control" data-filter-fare-family data-filter-key="fare_family"><option value="">Any</option></select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Layover airport</label>
                            <select class="form-control" data-filter-layover-airport data-filter-key="layover_airport"><option value="">Any</option></select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Operating airline</label>
                            <select class="form-control" data-filter-operating-airline data-filter-key="operating_airline"><option value="">Any</option></select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Refundable only</label>
                            <div class="checkbox"><label><input type="checkbox" data-filter-refundable data-filter-key="refundable"> Yes</label></div>
                        </div>
                        <div class="form-group checkbox">
                            <label><input type="checkbox" data-filter-bookable-only data-filter-key="bookable_only"> Bookable only</label>
                        </div>
                        <button type="button" class="btn btn-default btn-block" data-filter-reset>Clear filters</button>
                        <button type="button" class="btn btn-primary btn-block visible-xs" data-mobile-filter-apply>Apply filters</button>
                        <button type="button" class="btn btn-link btn-block visible-xs" data-mobile-filter-close>Close</button>
                        <p class="small text-muted" style="margin-top:8px;">Refine options by stop, time, and carrier preferences.</p>
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
    var mobileOpen = document.querySelector('[data-mobile-filter-open]');
    var mobileApply = document.querySelector('[data-mobile-filter-apply]');
    var mobileClose = document.querySelector('[data-mobile-filter-close]');
    var activeCountNode = document.querySelector('[data-active-filter-count]');
    var drawer = document.querySelector('[data-filter-drawer]');
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

    function cardHtml(offer) {
        var logoHtml = offer.airline_logo_url
            ? '<div class="ota-airline-logo ota-airline-logo--img"><img src="' + offer.airline_logo_url + '" alt="' + (offer.airline_name || 'Airline') + ' logo"></div>'
            : '<div class="ota-airline-logo">' + (offer.airline_code || 'XX') + '</div>';
        var refundable = offer.refundable
            ? '<span class="label label-success">Refundable</span>'
            : '<span class="label label-warning">Non-refundable</span>';
        var detailsId = 'offer-details-' + offer.offer_id;
        var segmentLines = (offer.segments || []).map(function (segment) {
            return '<li>' + [segment.origin, segment.destination, segment.flight_number].filter(Boolean).join(' · ') + '</li>';
        }).join('');
        return '' +
            '<article class="ota-result-pro-card"><div class="row">' +
            '<div class="col-sm-2 text-center">' + logoHtml +
            '<div class="ota-airline-name">' + (offer.airline_name || '') + '</div>' +
            '<div class="ota-flight-no">' + (offer.airline_code || '') + '</div></div>' +
            '<div class="col-sm-6"><div class="row ota-time-row">' +
            '<div class="col-xs-4"><div class="ota-time-lg">' + (offer.departure_time || '') + '</div><div class="ota-time-sub">{{ $criteria['origin'] }}</div></div>' +
            '<div class="col-xs-4 text-center"><div class="ota-dur-line">' + (offer.duration || '') + '</div><div class="ota-dur-bar"></div><span class="label label-default" style="font-size:10px;">' + ((offer.stops || 0) === 0 ? 'Direct' : (offer.stops + ' stop(s)')) + '</span></div>' +
            '<div class="col-xs-4 text-right"><div class="ota-time-lg">' + (offer.arrival_time || '') + '</div><div class="ota-time-sub">{{ $criteria['destination'] }}</div></div>' +
            '</div><div class="ota-result-tags"><span><i class="fa fa-suitcase"></i> ' + (offer.baggage || '') + '</span></div></div>' +
            '<div class="col-sm-4 text-right">' + refundable +
            '<div class="ota-price-stack"><div class="ota-price-lg">' + (offer.price_display || 'PKR fare unavailable') + '</div><div class="ota-price-sub">' + (offer.price_note || '') + '</div><div class="ota-pay-later">Continue to review before booking is submitted.</div></div>' +
            (offer.can_book
                ? '<a class="btn btn-primary btn-block ota-select-primary" href="' + offer.select_url + '">Book Now</a>'
                : '<div class="small text-muted" style="margin-bottom:8px;">' + (offer.disabled_reason || 'This fare cannot be booked online.') + '</div><button type="button" class="btn btn-default btn-block" disabled>Not available to book</button>') +
            '</div>' +
            '</div><div class="row" style="margin-top:8px;"><div class="col-xs-12">' +
            '<button class="btn btn-link" type="button" data-toggle-details="' + detailsId + '">Flight details ▼</button>' +
            '<div id="' + detailsId + '" style="display:none;border-top:1px solid #eee;padding-top:8px;">' +
            ((offer.flight_number || '') ? '<div><strong>Flight:</strong> ' + (offer.airline_name || '') + ' ' + (offer.airline_code || '') + ' ' + (offer.flight_number || '') + '</div>' : '') +
            ((offer.cabin || offer.fare_family) ? '<div><strong>Cabin:</strong> ' + (offer.cabin || '-') + ' · <strong>Fare:</strong> ' + (offer.fare_family || '-') + '</div>' : '') +
            ((offer.baggage || '') ? '<div><strong>Baggage:</strong> ' + (offer.baggage || '-') + '</div>' : '') +
            (segmentLines ? '<div><strong>Segments:</strong><ul>' + segmentLines + '</ul></div>' : '') +
            '<div class="small text-muted">Price shown in PKR after live FX conversion where applicable.</div>' +
            '</div></div></div></article>';
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
            parts.push('<button type="button" class="btn btn-default btn-xs" data-chip-remove="' + key + '">' + currentFilters[key] + ' ×</button>');
        });
        chips.innerHTML = parts.join(' ');
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
        if (activeCountNode) {
            activeCountNode.textContent = String(parts.length);
        }
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
                var open = block.style.display !== 'none';
                block.style.display = open ? 'none' : '';
                btn.textContent = open ? 'Flight details ▼' : 'Hide details ▲';
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
            if (window.innerWidth < 768 && key !== 'sort') return;
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
    if (mobileOpen && drawer) mobileOpen.addEventListener('click', function () { if (window.innerWidth < 768) drawer.style.display = 'block'; });
    if (mobileClose && drawer) mobileClose.addEventListener('click', function () { if (window.innerWidth < 768) drawer.style.display = 'none'; });
    if (mobileApply && drawer) mobileApply.addEventListener('click', function () { applyFilters(); if (window.innerWidth < 768) drawer.style.display = 'none'; });

    loadMore.addEventListener('click', function () { fetchPage(false); });
    fetchPage(true);
})();
</script>
@endpush
