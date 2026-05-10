@extends('layouts.dashboard')

@section('title', 'Bookings')

@push('styles')
<style>
    [data-bookings-page] { max-width: 1540px; margin: 0 auto; }
    .bookings-kpi .card { border: 1px solid rgba(98,105,118,.16); }
    .bookings-kpi .h2 { font-size: 1.4rem; }
    .bookings-kpi-link { display: block; color: inherit; text-decoration: none; border-radius: .5rem; }
    .bookings-kpi-link .card { transition: border-color .15s ease, box-shadow .15s ease; }
    .bookings-kpi-link:hover .card { border-color: #93c5fd; box-shadow: 0 4px 14px rgba(37,99,235,.08); }
    .bookings-kpi-link.is-active .card { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,.18); background: #f8fbff; }
    .bookings-queue-tabs { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .9rem; }
    .bookings-queue-tab { border: 1px solid rgba(98,105,118,.2); border-radius: 999px; padding: .38rem .72rem; font-size: .8rem; font-weight: 600; color: #475569; text-decoration: none; background: #fff; }
    .bookings-queue-tab.is-active { background: #e0edff; border-color: #93c5fd; color: #1d4ed8; }
    .bookings-filters { background: #f8fafc; border-radius: 8px; padding: 1rem 1.1rem; border: 1px solid rgba(98,105,118,.12); }
    .bookings-filters .form-label { font-size: .72rem; letter-spacing: .04em; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: .3rem; }
    .bookings-filters details > summary { cursor: pointer; user-select: none; }
    .bookings-table-wrap { border-radius: 8px; overflow: hidden; border: 1px solid rgba(98,105,118,.12); }
    .bookings-cards { padding: .75rem; display: grid; gap: .65rem; }
    .booking-queue-card { border: 1px solid rgba(148,163,184,.35); border-radius: 10px; background: #fff; padding: .75rem .85rem; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; outline: none; }
    .booking-queue-card:hover { border-color: #93c5fd; box-shadow: 0 4px 16px rgba(37,99,235,.08); }
    .booking-queue-card.is-active { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,.18); background: #f8fbff; }
    .booking-card-top { display: flex; justify-content: space-between; align-items: center; gap: .45rem; margin-bottom: .2rem; }
    .booking-card-ref { font-weight: 700; color: #0f172a; text-decoration: none; }
    .booking-card-statusline { font-size: .75rem; font-weight: 600; color: #475569; }
    .booking-card-passenger { font-size: .9rem; font-weight: 600; color: #0f172a; margin-bottom: .15rem; }
    .booking-card-trip, .booking-card-meta { font-size: .79rem; color: #475569; margin-bottom: .12rem; }
    .booking-card-actions { display: flex; gap: .45rem; flex-wrap: wrap; margin-top: .45rem; }
    .booking-card-actions .btn { padding: .22rem .58rem; font-size: .76rem; }
    .bookings-empty-state { border: 1px dashed rgba(148,163,184,.5); border-radius: 10px; padding: 1.5rem .75rem; text-align: center; color: #64748b; background: #f8fafc; }
    .bookings-meta { font-size: .74rem; color: #64748b; margin-top: .15rem; line-height: 1.35; }
    .booking-row-clickable { cursor: pointer; }
    .bookings-loading { opacity: .65; pointer-events: none; transition: opacity .15s ease; }
    .badge-soft-danger { background: #fee2e2; color: #991b1b !important; border: 1px solid #fecaca; }
    .badge-soft-warning { background: #fef3c7; color: #92400e !important; border: 1px solid #fde68a; }
    .badge-soft-success { background: #dcfce7; color: #166534 !important; border: 1px solid #bbf7d0; }
    .badge-soft-neutral { background: #e5e7eb; color: #374151 !important; border: 1px solid #d1d5db; }
    .badge-soft-info { background: #dbeafe; color: #1e40af !important; border: 1px solid #bfdbfe; }
    .bookings-preview { position: sticky; top: 24px; }
    .bookings-preview .card { border: 1px solid rgba(98,105,118,.16); box-shadow: 0 4px 24px rgba(15,23,42,.06); }
    .bookings-preview h4 { font-size: 1rem; font-weight: 600; margin-bottom: .75rem; color: #1e293b; }
    .preview-trip-line { font-size: .86rem; color: #475569; margin-bottom: .15rem; }
    .preview-block { border: 1px solid rgba(148,163,184,.28); border-radius: 8px; padding: .65rem .75rem; margin-bottom: .6rem; background: #fff; }
    .preview-kv { display: flex; justify-content: space-between; align-items: center; font-size: .86rem; padding: .12rem 0; }
    .preview-kv strong { color: #0f172a; }
    .preview-actions { display: grid; gap: .45rem; }
    .fare-line { display: flex; justify-content: space-between; padding: .35rem 0; border-bottom: 1px dashed rgba(98,105,118,.2); font-size: .875rem; }
    .fare-line:last-child { border-bottom: none; font-weight: 700; font-size: 1rem; padding-top: .5rem; }
    @media (max-width: 1024px) {
        .bookings-preview { position: static; }
    }
    @media (max-width: 640px) {
        .bookings-cards { padding: .5rem; }
        .booking-card-actions .btn { flex: 1 1 30%; text-align: center; }
    }
</style>
@endpush

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Operations</div>
            <h1 class="page-title">Bookings management</h1>
            <div class="text-secondary mt-1">Operational inbox for bookings queue, review, and assignment.</div>
        </div>
    </div>
@endsection

@section('content')
@php
    $b = $selectedBooking;
    $f = $filters ?? [];
    $staffOpts = $filterStaffUsers ?? collect();
    $statusCases = $statusEnumCases ?? [];
    $activeQueue = $activeQueue ?? ($f['queue'] ?? 'all');
    $queueTabs = [
        'all' => 'All bookings',
        'needs_action' => 'Needs action',
        'payment_review' => 'Payment review',
        'supplier_pnr' => 'Supplier / PNR',
        'ticketing' => 'Ticketing',
        'cancellations' => 'Cancellations',
        'refunds' => 'Refunds',
        'invoices' => 'Invoices',
        'documents' => 'Documents',
    ];
@endphp

<div data-bookings-page data-bookings-list>
    <div class="row row-cards bookings-kpi mb-3" data-bookings-kpis>
        <div class="col-sm-6 col-xl-3">
            <a class="bookings-kpi-link {{ $activeQueue === 'all' ? 'is-active' : '' }}" href="{{ route('admin.bookings', array_merge(request()->except('page'), ['queue' => 'all'])) }}">
                <div class="card card-sm"><div class="card-body"><div class="text-secondary small">Total bookings</div><div class="h2 mb-0">{{ number_format($kpis['total'] ?? 0) }}</div></div></div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="bookings-kpi-link {{ $activeQueue === 'needs_action' ? 'is-active' : '' }}" href="{{ route('admin.bookings', array_merge(request()->except('page'), ['queue' => 'needs_action'])) }}">
                <div class="card card-sm"><div class="card-body"><div class="text-secondary small">Needs action</div><div class="h2 mb-0 text-warning">{{ number_format($kpis['needs_action'] ?? 0) }}</div></div></div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="bookings-kpi-link {{ $activeQueue === 'payment_review' ? 'is-active' : '' }}" href="{{ route('admin.bookings', array_merge(request()->except('page'), ['queue' => 'payment_review'])) }}">
                <div class="card card-sm"><div class="card-body"><div class="text-secondary small">Payment pending</div><div class="h2 mb-0 text-danger">{{ number_format($kpis['payment_pending'] ?? 0) }}</div></div></div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="bookings-kpi-link {{ $activeQueue === 'ticketing' ? 'is-active' : '' }}" href="{{ route('admin.bookings', array_merge(request()->except('page'), ['queue' => 'ticketing'])) }}">
                <div class="card card-sm"><div class="card-body"><div class="text-secondary small">Ticketing pending</div><div class="h2 mb-0 text-primary">{{ number_format($kpis['ticketing_pending'] ?? 0) }}</div></div></div>
            </a>
        </div>
    </div>

    <div class="bookings-queue-tabs" data-bookings-tabs>
        @foreach ($queueTabs as $queueKey => $queueLabel)
            <a href="{{ route('admin.bookings', array_merge(request()->except('page'), ['queue' => $queueKey])) }}" class="bookings-queue-tab {{ $activeQueue === $queueKey ? 'is-active' : '' }}">{{ $queueLabel }}</a>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-8 col-lg-7">
            <div class="bookings-filters mb-3" data-bookings-filter-bar>
                <form method="get" action="{{ route('admin.bookings') }}" class="row g-2 align-items-end" id="bookings-filter-form">
                    <input type="hidden" name="queue" value="{{ $activeQueue }}">
                    <div class="col-xl-4 col-lg-5 col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ $f['search'] ?? '' }}" class="form-control" placeholder="Search booking, customer, phone" id="bookings-search-input" list="bookings-search-suggestions" autocomplete="off">
                        <datalist id="bookings-search-suggestions"></datalist>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach ($statusCases as $sc)
                                <option value="{{ $sc->value }}" @selected(($f['status'] ?? '') === $sc->value)>{{ str_replace('_', ' ', $sc->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-6">
                        <label class="form-label">Payment</label>
                        <select name="payment_status" class="form-select">
                            <option value="">All</option>
                            @foreach (['unpaid', 'partial', 'paid', 'refunded'] as $ps)
                                <option value="{{ $ps }}" @selected(($f['payment_status'] ?? '') === $ps)>{{ ucfirst($ps) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <label class="form-label">Assigned staff</label>
                        <select name="assigned_staff_id" class="form-select">
                            <option value="">Any</option>
                            @foreach ($staffOpts as $su)
                                <option value="{{ $su->id }}" @selected((string)($f['assigned_staff_id'] ?? '') === (string) $su->id)>{{ $su->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-1 col-lg-3 col-md-6">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" value="{{ $f['date_from'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-xl-1 col-lg-3 col-md-6">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" value="{{ $f['date_to'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <details>
                            <summary class="small text-secondary">More filters</summary>
                            <div class="row g-2 mt-1">
                                <div class="col-md-3"><label class="form-label">Airline</label><input type="text" name="airline" value="{{ $f['airline'] ?? '' }}" class="form-control" placeholder="Emirates"></div>
                                <div class="col-md-3"><label class="form-label">Route</label><input type="text" name="route" value="{{ $f['route'] ?? '' }}" class="form-control" placeholder="LHE - DXB"></div>
                                <div class="col-md-2">
                                    <label class="form-label">Agent/customer</label>
                                    <select name="agent_customer" class="form-select">
                                        <option value="">Any</option>
                                        <option value="agent" @selected(($f['agent_customer'] ?? '') === 'agent')>Agent</option>
                                        <option value="customer" @selected(($f['agent_customer'] ?? '') === 'customer')>Customer</option>
                                        <option value="guest" @selected(($f['agent_customer'] ?? '') === 'guest')>Guest</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Booking type</label>
                                    <select name="booking_type" class="form-select">
                                        <option value="">Any</option>
                                        <option value="public" @selected(($f['booking_type'] ?? '') === 'public')>Public</option>
                                        <option value="agent_portal" @selected(($f['booking_type'] ?? '') === 'agent_portal')>Agent portal</option>
                                        <option value="direct" @selected(($f['booking_type'] ?? '') === 'direct')>Direct</option>
                                    </select>
                                </div>
                                <div class="col-md-2"><label class="form-label">Fare min (PKR)</label><input type="number" min="0" step="1" name="fare_min" value="{{ $f['fare_min'] ?? '' }}" class="form-control" placeholder="0"></div>
                                <div class="col-md-2"><label class="form-label">Fare max (PKR)</label><input type="number" min="0" step="1" name="fare_max" value="{{ $f['fare_max'] ?? '' }}" class="form-control" placeholder="500000"></div>
                                <div class="col-md-6"><label class="form-label">Created date</label><div class="form-control-plaintext small text-secondary">Use the top bar date range (From/To).</div></div>
                                <div class="col-md-3"><label class="form-label">Travel from</label><input type="date" name="travel_date_from" value="{{ $f['travel_date_from'] ?? '' }}" class="form-control"></div>
                                <div class="col-md-3"><label class="form-label">Travel to</label><input type="date" name="travel_date_to" value="{{ $f['travel_date_to'] ?? '' }}" class="form-control"></div>
                            </div>
                        </details>
                    </div>
                    <div class="col-12 mt-2 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">Apply filters</button>
                        <a href="{{ route('admin.bookings') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="card bookings-table-wrap" data-bookings-list>
                <div class="card-header border-0 pb-0"><h3 class="card-title">All bookings</h3></div>
                <div id="bookings-table-body" class="bookings-cards">
                    @forelse ($bookings as $row)
                        @php
                            $ctype = $row['customer_type'] ?? 'guest';
                            $st = $row['status'] ?? 'pending';
                            $stDisplay = $row['status_display'] ?? ucfirst(str_replace('_', ' ', $st));
                            $pay = $row['payment_status'] ?? 'unpaid';
                            $payDisplay = $row['payment_status_display'] ?? ucfirst(str_replace('_', ' ', $pay));
                            $refDisplay = ($row['booking_ref'] ?? '') !== '' ? $row['booking_ref'] : ('Draft #'.($row['id'] ?? ''));
                            $isSelected = isset($selectedPreviewKey) && (string)($row['preview_query'] ?? '') === (string)$selectedPreviewKey;
                            $previewUrl = route('admin.bookings', array_merge(request()->except('preview'), ['preview' => $row['preview_query']]));
                        @endphp
                        <article class="booking-queue-card {{ $isSelected ? 'is-active' : '' }}" data-booking-row data-preview-url="{{ $previewUrl }}" data-booking-id="{{ $row['id'] }}" data-preview-key="{{ $row['preview_query'] }}" tabindex="0" role="button" aria-label="Preview booking {{ $refDisplay }}">
                                <div class="booking-card-top">
                                <a href="{{ $previewUrl }}" class="booking-card-ref">{{ $refDisplay }}</a>
                                    <div class="booking-card-statusline">{{ $stDisplay }} · {{ $payDisplay }}</div>
                            </div>
                            <div class="booking-card-passenger">{{ $row['customer_name'] }}</div>
                            <div class="booking-card-trip">{{ $row['route'] }} · {{ $row['airline'] }} · {{ $row['travel_date'] }}</div>
                            <div class="booking-card-meta">Rs {{ number_format((int) ($row['total_fare'] ?? 0), 0) }} · {{ ucfirst($ctype) }} · {{ (int)($row['passengers_count'] ?? 0) }} passenger{{ (int)($row['passengers_count'] ?? 0) === 1 ? '' : 's' }}</div>
                            <div class="booking-card-actions">
                                <a href="{{ route('admin.bookings.show', $row['id']) }}" class="btn btn-sm btn-outline-primary" data-booking-open-link>Open</a>
                                <a href="{{ route('admin.bookings.show', $row['id']) }}#assignment" class="btn btn-sm btn-outline-secondary">Assign</a>
                                <a href="{{ route('admin.bookings.show', $row['id']) }}#payments" class="btn btn-sm btn-outline-secondary">Payment</a>
                            </div>
                        </article>
                    @empty
                        <div class="bookings-empty-state">No bookings found. Try adjusting filters or create/search a booking.</div>
                    @endforelse
                </div>
                @if ($bookings instanceof \Illuminate\Contracts\Pagination\Paginator && $bookings->hasPages())
                    <div class="card-footer d-flex justify-content-center" id="bookings-pagination-wrap">{{ $bookings->links() }}</div>
                @else
                    <div class="card-footer d-flex justify-content-center" id="bookings-pagination-wrap"></div>
                @endif
            </div>
        </div>

        <div class="col-xl-4 col-lg-5" data-booking-preview data-bookings-preview>
            <div class="bookings-preview">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Selected booking</h3>
                        <div class="card-subtitle text-secondary" id="bookings-preview-subtitle">
                            @if (($previewRef ?? '') !== '') Preview: <code>{{ $previewRef }}</code> @else Default preview (first row). @endif
                        </div>
                    </div>
                    <div class="card-body" id="bookings-preview-body">
                        @if ($b)
                            @php
                                $previewRef = ($b['booking_ref'] ?? '') !== '' ? $b['booking_ref'] : ('Draft #'.($b['id'] ?? ''));
                                $travelLabel = ($b['travel_date'] ?? '—') !== '—' ? \Illuminate\Support\Carbon::parse($b['travel_date'])->format('d M Y') : '—';
                                $paxCount = (int) ($b['passengers_count'] ?? 0);
                                $totalFare = (int) ($b['total_fare'] ?? 0);
                            @endphp
                            <h4 class="mb-1">{{ $previewRef }}</h4>
                            <div class="preview-trip-line">{{ $b['route'] }} · {{ $b['airline'] }}</div>
                            <div class="preview-trip-line mb-3">{{ $travelLabel }}</div>

                            <h4>Customer</h4>
                            <div class="preview-block">
                                <div class="fw-semibold">{{ $b['customer_name'] }}</div>
                                <div class="small text-secondary mb-2">{{ ucfirst($b['customer_type'] ?? 'guest') }} · {{ $paxCount }} passenger{{ $paxCount === 1 ? '' : 's' }}</div>
                                <div class="small text-secondary">{{ $b['contact_phone'] }} / {{ $b['contact_email'] }}</div>
                            </div>

                            <h4>Financial</h4>
                            <div class="preview-block">
                                <div class="preview-kv"><span>Total:</span><strong>Rs {{ number_format($totalFare, 0) }}</strong></div>
                                <div class="preview-kv"><span>Paid:</span><strong>Rs 0</strong></div>
                                <div class="preview-kv"><span>Balance:</span><strong>Rs {{ number_format($totalFare, 0) }}</strong></div>
                            </div>

                            <h4>Current status</h4>
                            <div class="preview-block">
                                <div class="preview-kv"><span>Booking:</span><strong>{{ ucfirst(str_replace('_', ' ', $b['status'] ?? 'draft')) }}</strong></div>
                                <div class="preview-kv"><span>Payment:</span><strong>{{ $b['payment_status_display'] ?? ucfirst(str_replace('_', ' ', $b['payment_status'] ?? 'unpaid')) }}</strong></div>
                                <div class="preview-kv"><span>Supplier:</span><strong>{{ $b['supplier_status_display'] ?? 'not started' }}</strong></div>
                                <div class="preview-kv"><span>Ticketing:</span><strong>{{ $b['ticketing_status_display'] ?? 'not started' }}</strong></div>
                                <div class="preview-kv"><span>Assigned:</span><strong>{{ $b['assigned_staff_name'] ?? 'Unassigned' }}</strong></div>
                            </div>

                            <h4>Next action</h4>
                            <div class="preview-actions">
                                <a href="{{ route('admin.bookings.show', $b['id']) }}" class="btn btn-primary">Open full record</a>
                                <a href="{{ route('admin.bookings.show', $b['id']) }}#payments" class="btn btn-outline-secondary">Record payment</a>
                                <a href="{{ route('admin.bookings.show', $b['id']) }}#assignment" class="btn btn-outline-secondary">Assign staff</a>
                            </div>
                        @else
                            <p class="text-secondary mb-0">No booking selected.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('bookings-filter-form');
        var cardsWrap = document.getElementById('bookings-table-body');
        var paginationWrap = document.getElementById('bookings-pagination-wrap');
        var previewBody = document.getElementById('bookings-preview-body');
        var previewSubtitle = document.getElementById('bookings-preview-subtitle');
        var searchInput = document.getElementById('bookings-search-input');
        var suggestionsList = document.getElementById('bookings-search-suggestions');
        if (!form || !cardsWrap || !paginationWrap || !previewBody || !previewSubtitle) return;

        var dataUrl = @json(route('admin.bookings.data'));
        var suggestionsUrl = @json(route('admin.bookings.suggestions'));
        var previewBaseUrl = @json(url('/admin/bookings'));
        var state = {
            page: 1,
            preview: @json($selectedPreviewKey ?? ''),
            loading: false,
            previewLoading: false,
            searchTimer: null,
            suggestTimer: null
        };

        function esc(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function badgeClass(type, value) {
            if (type === 'customer_type') {
                return value === 'agent' ? 'bg-warning' : (value === 'customer' ? 'bg-primary' : 'bg-secondary');
            }
            if (type === 'status') {
                if (value === 'ticketed') return 'bg-success';
                if (value === 'confirmed') return 'bg-info';
                if (value === 'cancelled') return 'bg-dark';
                if (value === 'draft') return 'bg-secondary';
                return 'bg-warning';
            }
            if (type === 'payment_status') {
                if (value === 'paid') return 'bg-success';
                if (value === 'partial') return 'bg-warning';
                if (value === 'refunded') return 'bg-secondary';
                return 'bg-danger';
            }
            return 'bg-secondary';
        }

        function rowHtml(row, selectedKey) {
            var ctype = row.customer_type || 'guest';
            var st = row.status || 'pending';
            var stDisplay = row.status_display || String(st).replace('_', ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
            var pay = row.payment_status || 'unpaid';
            var payDisplay = row.payment_status_display || String(pay).replace('_', ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
            var isSelected = String(row.preview_query || '') === String(selectedKey || '');
            var refDisplay = (row.booking_ref || '') !== '' ? row.booking_ref : ('Draft #' + (row.id || ''));
            var searchParams = new URLSearchParams(currentFilters());
            searchParams.set('preview', String(row.preview_query || ''));
            var previewUrl = @json(route('admin.bookings')) + '?' + searchParams.toString();
            var paxCount = Number(row.passengers_count || 0);
            return '' +
                '<article class="booking-queue-card ' + (isSelected ? 'is-active ' : '') + '" data-booking-row data-booking-id="' + esc(row.id || '') + '" data-preview-key="' + esc(row.preview_query || '') + '" data-preview-url="' + esc(previewUrl) + '" tabindex="0" role="button" aria-label="Preview booking ' + esc(refDisplay) + '">' +
                '<div class="booking-card-top"><a href="' + esc(previewUrl) + '" class="booking-card-ref">' + esc(refDisplay) + '</a><div class="booking-card-statusline">' + esc(stDisplay) + ' · ' + esc(payDisplay) + '</div></div>' +
                '<div class="booking-card-passenger">' + esc(row.customer_name || '') + '</div>' +
                '<div class="booking-card-trip">' + esc(row.route || '—') + ' · ' + esc(row.airline || '—') + ' · ' + esc(row.travel_date || '—') + '</div>' +
                '<div class="booking-card-meta">Rs ' + esc(Number(row.total_fare || 0).toLocaleString()) + ' · ' + esc(ctype.charAt(0).toUpperCase() + ctype.slice(1)) + ' · ' + esc(paxCount) + ' passenger' + (paxCount === 1 ? '' : 's') + '</div>' +
                '<div class="booking-card-actions"><a href="' + esc(@json(url('/admin/bookings')) + '/' + row.id) + '" class="btn btn-sm btn-outline-primary" data-booking-open-link>Open</a><a href="' + esc(@json(url('/admin/bookings')) + '/' + row.id + '#assignment') + '" class="btn btn-sm btn-outline-secondary">Assign</a><a href="' + esc(@json(url('/admin/bookings')) + '/' + row.id + '#payments') + '" class="btn btn-sm btn-outline-secondary">Payment</a></div>' +
                '</article>';
        }

        function previewHtml(b) {
            if (!b) return '<p class="text-secondary mb-0">No booking selected.</p>';
            var ref = (b.booking_ref || '') !== '' ? b.booking_ref : ('Draft #' + (b.id || ''));
            var ctype = b.customer_type || 'guest';
            var paxCount = Number(b.passengers_count || 0);
            var totalFare = Number(b.total_fare || 0);
            var travelLabel = (function () {
                if (!b.travel_date || b.travel_date === '—') return '—';
                var d = new Date(b.travel_date);
                if (isNaN(d.getTime())) return esc(b.travel_date);
                return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
            })();
            return '' +
                '<h4 class="mb-1">' + esc(ref) + '</h4>' +
                '<div class="preview-trip-line">' + esc(b.route || '—') + ' · ' + esc(b.airline || '—') + '</div>' +
                '<div class="preview-trip-line mb-3">' + esc(travelLabel) + '</div>' +
                '<h4>Customer</h4>' +
                '<div class="preview-block"><div class="fw-semibold">' + esc(b.customer_name || '') + '</div><div class="small text-secondary mb-2">' + esc(ctype.charAt(0).toUpperCase() + ctype.slice(1)) + ' · ' + esc(paxCount) + ' passenger' + (paxCount === 1 ? '' : 's') + '</div><div class="small text-secondary">' + esc(b.contact_phone || '—') + ' / ' + esc(b.contact_email || '—') + '</div></div>' +
                '<h4>Financial</h4>' +
                '<div class="preview-block"><div class="preview-kv"><span>Total:</span><strong>Rs ' + esc(totalFare.toLocaleString()) + '</strong></div><div class="preview-kv"><span>Paid:</span><strong>Rs 0</strong></div><div class="preview-kv"><span>Balance:</span><strong>Rs ' + esc(totalFare.toLocaleString()) + '</strong></div></div>' +
                '<h4>Current status</h4>' +
                '<div class="preview-block"><div class="preview-kv"><span>Booking:</span><strong>' + esc(String(b.status || 'draft').replace('_', ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); })) + '</strong></div><div class="preview-kv"><span>Payment:</span><strong>' + esc((b.payment_status_display || String(b.payment_status || 'unpaid').replace('_', ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); }))) + '</strong></div><div class="preview-kv"><span>Supplier:</span><strong>' + esc((b.supplier_status_display || 'not started')) + '</strong></div><div class="preview-kv"><span>Ticketing:</span><strong>' + esc((b.ticketing_status_display || 'not started')) + '</strong></div><div class="preview-kv"><span>Assigned:</span><strong>' + esc(b.assigned_staff_name || 'Unassigned') + '</strong></div></div>' +
                '<h4>Next action</h4>' +
                '<div class="preview-actions"><a href="' + esc(@json(url('/admin/bookings')) + '/' + b.id) + '" class="btn btn-primary">Open full record</a><a href="' + esc(@json(url('/admin/bookings')) + '/' + b.id + '#payments') + '" class="btn btn-outline-secondary">Record payment</a><a href="' + esc(@json(url('/admin/bookings')) + '/' + b.id + '#assignment') + '" class="btn btn-outline-secondary">Assign staff</a></div>';
        }

        function currentFilters() {
            var fd = new FormData(form);
            var out = {};
            fd.forEach(function (v, k) {
                if (String(v).trim() !== '') out[k] = String(v);
            });
            return out;
        }

        function setLoading(on) {
            state.loading = on;
            var wrap = document.querySelector('.bookings-table-wrap');
            if (wrap) wrap.classList.toggle('bookings-loading', on);
        }

        function renderPagination(meta) {
            if (!meta || !meta.total) {
                paginationWrap.innerHTML = '';
                return;
            }
            var prevDisabled = !meta.prev_page_url ? 'disabled' : '';
            var nextDisabled = !meta.next_page_url ? 'disabled' : '';
            paginationWrap.innerHTML = '' +
                '<div class="d-flex align-items-center gap-2 flex-wrap w-100 justify-content-between">' +
                '<div class="text-secondary small">Showing ' + esc(meta.from || 0) + ' - ' + esc(meta.to || 0) + ' of ' + esc(meta.total || 0) + '</div>' +
                '<div class="btn-group">' +
                '<button type="button" class="btn btn-outline-secondary btn-sm" data-page-nav="prev" ' + prevDisabled + '>Previous</button>' +
                '<button type="button" class="btn btn-outline-secondary btn-sm" data-page-nav="next" ' + nextDisabled + '>Next</button>' +
                '</div></div>';
            var prevBtn = paginationWrap.querySelector('[data-page-nav="prev"]');
            var nextBtn = paginationWrap.querySelector('[data-page-nav="next"]');
            if (prevBtn) prevBtn.addEventListener('click', function () { fetchRows(Math.max(1, meta.current_page - 1)); });
            if (nextBtn) nextBtn.addEventListener('click', function () { fetchRows(Math.min(meta.last_page, meta.current_page + 1)); });
        }

        function fetchRows(page) {
            if (state.loading) return Promise.resolve();
            state.page = page || 1;
            var params = new URLSearchParams(currentFilters());
            params.set('page', String(state.page));
            params.set('per_page', '25');
            if (state.preview) params.set('preview', state.preview);
            setLoading(true);
            return fetch(dataUrl + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                .then(function (json) {
                    var rows = Array.isArray(json.rows) ? json.rows : [];
                    state.preview = String(json.selected_preview_key || '');
                    if (!rows.length) {
                        cardsWrap.innerHTML = '<div class="bookings-empty-state">No bookings found for current filters.</div>';
                    } else {
                        cardsWrap.innerHTML = rows.map(function (row) { return rowHtml(row, state.preview); }).join('');
                    }
                    previewBody.innerHTML = previewHtml(json.selected_booking || null);
                    previewSubtitle.innerHTML = state.preview ? ('Preview: <code>' + esc(state.preview) + '</code>') : 'Default preview (first row).';
                    renderPagination(json.pagination || null);
                })
                .finally(function () { setLoading(false); });
        }

        function highlightSelected(previewKey) {
            var rows = cardsWrap.querySelectorAll('[data-booking-id]');
            rows.forEach(function (r) {
                var active = String(r.getAttribute('data-preview-key') || '') === String(previewKey || '');
                r.classList.toggle('is-active', active);
            });
        }

        function setPreviewLoading(on) {
            state.previewLoading = on;
            if (previewBody) previewBody.classList.toggle('bookings-loading', on);
        }

        function syncPreviewInUrl(previewKey) {
            try {
                var url = new URL(window.location.href);
                if (previewKey && String(previewKey).trim() !== '') {
                    url.searchParams.set('preview', String(previewKey));
                } else {
                    url.searchParams.delete('preview');
                }
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}
        }

        function fetchPreviewForRow(row) {
            if (state.previewLoading) return;
            var bookingId = row.getAttribute('data-booking-id');
            if (!bookingId) return;
            var previewKey = row.getAttribute('data-preview-key') || '';
            var previewUrl = previewBaseUrl + '/' + encodeURIComponent(String(bookingId)) + '/preview';
            setPreviewLoading(true);
            fetch(previewUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                .then(function (json) {
                    var selected = json && (json.selected_booking || json.booking) ? (json.selected_booking || json.booking) : null;
                    state.preview = String((json && (json.selected_preview_key || json.preview_key)) || (selected && selected.preview_query) || previewKey || '');
                    previewBody.innerHTML = previewHtml(selected);
                    var subtitleRef = json && json.preview_ref ? String(json.preview_ref) : state.preview;
                    previewSubtitle.innerHTML = subtitleRef ? ('Preview: <code>' + esc(subtitleRef) + '</code>') : 'Default preview (first row).';
                    highlightSelected(state.preview);
                    syncPreviewInUrl(state.preview);
                })
                .catch(function () {
                    var fallbackUrl = row.getAttribute('data-preview-url');
                    if (fallbackUrl) window.location.href = fallbackUrl;
                })
                .finally(function () {
                    setPreviewLoading(false);
                });
        }

        cardsWrap.addEventListener('click', function (event) {
            var row = event.target.closest('[data-booking-id]');
            if (!row) return;
            if (event.target.closest('[data-booking-open-link]')) return;
            var link = event.target.closest('a');
            if (link) {
                event.preventDefault();
            }
            fetchPreviewForRow(row);
        });
        cardsWrap.addEventListener('keydown', function (event) {
            var row = event.target.closest('[data-booking-id]');
            if (!row) return;
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            fetchPreviewForRow(row);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            state.page = 1;
            state.preview = '';
            fetchRows(1).catch(function () {
                form.submit();
            });
        });
        form.querySelectorAll('select,input[type="date"]').forEach(function (el) {
            el.addEventListener('change', function () {
                state.page = 1;
                state.preview = '';
                fetchRows(1).catch(function () {
                    form.submit();
                });
            });
        });
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                if (state.searchTimer) clearTimeout(state.searchTimer);
                state.searchTimer = setTimeout(function () {
                    state.page = 1;
                    state.preview = '';
                    fetchRows(1).catch(function () {
                        form.submit();
                    });
                }, 260);

                var q = (searchInput.value || '').trim();
                if (state.suggestTimer) clearTimeout(state.suggestTimer);
                if (q.length < 2) {
                    suggestionsList.innerHTML = '';
                    return;
                }
                state.suggestTimer = setTimeout(function () {
                    fetch(suggestionsUrl + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                        .then(function (json) {
                            var rows = Array.isArray(json.suggestions) ? json.suggestions : [];
                            suggestionsList.innerHTML = rows.map(function (s) {
                                return '<option value="' + esc(s.value || '') + '" label="' + esc(s.label || '') + '"></option>';
                            }).join('');
                        })
                        .catch(function () {});
                }, 180);
            });
        }
    })();
</script>
@endpush
