@extends('layouts.dashboard')

@section('title', 'Admin')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Overview</div>
            <h1 class="page-title">{{ $role }} dashboard</h1>
            <div class="text-secondary mt-1">
                Live operations cockpit — counts and queues are powered by booking records scoped by agency.
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $s = $stats ?? [];
        $recent = $recentBookings ?? [];
        $ops = $todayOperations ?? [];
        $suppliers = $supplierReadiness ?? [];
        $rev = $revenueSnapshot ?? [];
        $hasLiveData = (bool) ($hasLiveData ?? false);
        $opKpis = $operationalKpis ?? [];
        $attn = $needsAttention ?? [];
        $cb = $commandSummary ?? [];
        $tasks = $taskActions ?? [];
        $health = $supplierHealth ?? collect();

        $duffelHealthy = false;
        foreach ($health as $hRow) {
            if (($hRow['code'] ?? '') === 'DUFFEL' && ($hRow['status'] ?? '') === 'connected') {
                $duffelHealthy = true;
                break;
            }
        }
    @endphp

    @if (! $hasLiveData)
        <div class="alert alert-info mb-3">
            <i class="ti ti-info-circle me-1"></i>
            No live booking data yet. Metrics update automatically once bookings are created.
        </div>
    @endif

    {{-- PART A: Operations command banner --}}
    <div class="ota-command-banner mb-4" data-testid="ota-command-banner">
        <div class="d-flex flex-wrap align-items-start gap-3">
            <div class="flex-grow-1 min-w-0">
                <p class="ota-cb-title">Today's OTA operations</p>
                <h2 class="ota-cb-headline">Operator command center</h2>
                <div class="ota-cb-summary">
                    <span class="ota-cb-chip {{ ((int) ($cb['needs_action'] ?? 0)) > 0 ? 'ota-cb-chip--warn' : '' }}">
                        <i class="ti ti-alert-triangle"></i>
                        {{ (int) ($cb['needs_action'] ?? 0) }} bookings need action
                    </span>
                    <span class="ota-cb-chip {{ ((int) ($cb['payment_review'] ?? 0)) > 0 ? 'ota-cb-chip--warn' : '' }}">
                        <i class="ti ti-cash"></i>
                        {{ (int) ($cb['payment_review'] ?? 0) }} payments to review
                    </span>
                    <span class="ota-cb-chip {{ ((int) ($cb['ticketing_pending'] ?? 0)) > 0 ? 'ota-cb-chip--warn' : '' }}">
                        <i class="ti ti-ticket"></i>
                        {{ (int) ($cb['ticketing_pending'] ?? 0) }} ticketing tasks
                    </span>
                    <span class="ota-cb-chip {{ $duffelHealthy ? 'ota-cb-chip--good' : '' }}">
                        <i class="ti ti-plug-connected"></i>
                        Duffel: {{ $duffelHealthy ? 'Connected' : 'Not configured' }}
                    </span>
                    <span class="ota-cb-chip">
                        <i class="ti ti-coin"></i>
                        Rs {{ number_format((int) ($cb['gross_sales'] ?? 0)) }} gross sales
                    </span>
                </div>
            </div>
            <div class="ota-cb-actions">
                <a href="{{ route('admin.bookings', ['queue' => 'needs_action']) }}" class="btn btn-light">
                    <i class="ti ti-eye me-1"></i> Review bookings
                </a>
                <a href="{{ route('admin.bookings', ['queue' => 'payment_review']) }}" class="btn btn-outline-light">
                    Payment queue
                </a>
                <a href="{{ route('admin.bookings', ['queue' => 'supplier_pnr']) }}" class="btn btn-outline-light">
                    Supplier / PNR
                </a>
                <a href="{{ route('admin.bookings', ['queue' => 'ticketing']) }}" class="btn btn-outline-light">
                    Ticketing queue
                </a>
            </div>
        </div>
    </div>

    {{-- PART B: Operational KPI cards --}}
    <div class="row g-3 mb-4" data-testid="ota-op-kpi-row">
        @foreach ($opKpis as $kpi)
            @php
                $tone = (string) ($kpi['tone'] ?? 'muted');
                $params = (array) ($kpi['route_params'] ?? []);
                $href = route($kpi['route'] ?? 'admin.bookings', $params);
            @endphp
            <div class="col-6 col-lg-4 col-xl-2">
                <a href="{{ $href }}" class="ota-op-kpi ota-op-kpi--{{ $tone }} card-sm" data-testid="ota-op-kpi-{{ $kpi['key'] }}">
                    <div class="card-body">
                        <div class="ota-op-kpi-icon"><i class="ti {{ $kpi['icon'] ?? 'ti-flag' }}"></i></div>
                        <div class="ota-op-kpi-label">{{ $kpi['label'] }}</div>
                        <div class="ota-op-kpi-count">{{ number_format((int) ($kpi['count'] ?? 0)) }}</div>
                        <div class="ota-op-kpi-helper">{{ $kpi['helper'] ?? '' }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- PART D: Quick actions (task-based) --}}
    <div class="mb-4">
        <h3 class="h5 fw-bold mb-3">Quick actions</h3>
        <div class="row g-3 ota-admin-quick">
            @foreach ($tasks as $task)
                @php
                    $taskParams = (array) ($task['route_params'] ?? []);
                    $taskHref = route($task['route'] ?? 'admin.bookings', $taskParams);
                @endphp
                <div class="col-6 col-lg-4 col-xl-2">
                    <a href="{{ $taskHref }}" class="ota-quick-action-link text-reset text-decoration-none" data-testid="ota-quick-action-{{ $task['key'] }}">
                        <div class="card h-100 ota-quick-action-card">
                            <div class="card-body">
                                <div class="ota-quick-icon"><i class="ti {{ $task['icon'] ?? 'ti-bolt' }}"></i></div>
                                <div class="fw-bold">{{ $task['label'] }}</div>
                                <div class="text-secondary small mt-1">{{ $task['helper'] ?? '' }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="{{ route('admin.agents') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-users me-1"></i> Add agent
            </a>
            <a href="{{ route('admin.api-settings') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-api me-1"></i> API settings
            </a>
            <a href="{{ route('admin.reports') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-chart-bar me-1"></i> View reports
            </a>
        </div>
    </div>

    {{-- PART C + G + E + F: Two-column operations layout --}}
    <div class="row g-4 mb-4">
        {{-- Left column: needs attention + recent bookings + supplier health --}}
        <div class="col-lg-8">
            {{-- Needs attention --}}
            <div class="card ota-attn-card mb-4" data-testid="ota-needs-attention">
                <div class="card-header">
                    <h3 class="card-title mb-0">Needs attention</h3>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($attn as $row)
                        @php
                            $count = (int) ($row['count'] ?? 0);
                            $params = (array) ($row['route_params'] ?? []);
                            $href = route($row['route'] ?? 'admin.bookings', $params);
                        @endphp
                        <a href="{{ $href }}" class="list-group-item list-group-item-action" data-testid="ota-needs-attention-{{ $row['key'] }}">
                            <div class="ota-attn-row">
                                <div class="ota-attn-count {{ $count === 0 ? 'ota-attn-count--zero' : '' }}">{{ number_format($count) }}</div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="ota-attn-label">{{ $row['label'] }}</div>
                                    <div class="ota-attn-helper">{{ $row['helper'] ?? '' }}</div>
                                </div>
                                <div class="text-secondary small text-nowrap">
                                    View <i class="ti ti-chevron-right"></i>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Recent bookings --}}
            <div class="card border-0 shadow-sm ota-recent-bookings-card" data-testid="ota-recent-bookings">
                <div class="card-header border-0 ota-recent-card-head">
                    <div class="ota-recent-head">
                        <h3 class="ota-recent-head-title">Recent bookings</h3>
                        <p class="ota-recent-head-sub text-secondary small mb-0">
                            @if ($hasLiveData)
                                <span class="ota-recent-head-sub-line">Latest rows from</span>
                                <span class="ota-recent-source">live database bookings</span>
                            @else
                                <span class="ota-recent-head-sub-line">No live bookings available yet</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table ota-admin-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking</th>
                                <th>Route</th>
                                <th>Customer</th>
                                <th>Airline</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                                <th>Created</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $row)
                                @php
                                    $stStr = strtolower((string) ($row['status'] ?? ''));
                                    $bcls = match (true) {
                                        str_contains($stStr, 'cancel') => 'ota-bstat ota-bstat--cancelled',
                                        str_contains($stStr, 'ticket') => 'ota-bstat ota-bstat--ticketed',
                                        str_contains($stStr, 'pending') => 'ota-bstat ota-bstat--pending',
                                        str_contains($stStr, 'confirm') => 'ota-bstat ota-bstat--confirmed',
                                        default => 'ota-bstat ota-bstat--muted',
                                    };
                                    $previewParam = (string) ($row['preview_query'] ?? ($row['ref'] ?? ''));
                                    $openHref = route('admin.bookings', ['queue' => 'all', 'preview' => $previewParam]);
                                @endphp
                                <tr>
                                    <td class="fw-semibold">
                                        @if (($row['has_reference'] ?? true))
                                            <span class="text-secondary">{{ $row['ref'] }}</span>
                                        @else
                                            <span class="text-secondary">{{ $row['ref'] }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['route'] }}</td>
                                    <td>{{ $row['customer'] }}</td>
                                    <td>{{ $row['airline'] }}</td>
                                    <td class="text-capitalize">{{ $row['payment_status'] }}</td>
                                    <td><span class="{{ $bcls }}">{{ $row['status'] }}</span></td>
                                    <td class="text-end fw-semibold">Rs {{ number_format((float) $row['amount_pkr'], 0) }}</td>
                                    <td class="text-secondary small">{{ $row['created_at'] }}</td>
                                    <td class="text-end">
                                        <a href="{{ $openHref }}" class="btn btn-sm btn-outline-primary" data-testid="ota-recent-open">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-secondary py-4">No bookings to display yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent border-top-0 pt-0">
                    <a href="{{ route('admin.bookings') }}" class="btn btn-link p-0">Open bookings module <i class="ti ti-chevron-right"></i></a>
                </div>
            </div>
        </div>

        {{-- Right column: revenue snapshot, supplier health, legacy supplier readiness for compatibility --}}
        <div class="col-lg-4">
            {{-- PART G: Revenue snapshot --}}
            <div class="card mb-4" data-testid="ota-revenue-snapshot">
                <div class="card-header">
                    <h3 class="card-title mb-0">Revenue snapshot</h3>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-3">{{ $rev['period_label'] ?? '' }}</p>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Direct customer sales</span>
                        <span class="fw-bold">Rs {{ number_format((int) ($rev['direct_customer_sales'] ?? 0)) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Agent sales</span>
                        <span class="fw-bold">Rs {{ number_format((int) ($rev['agent_sales'] ?? 0)) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Markup revenue</span>
                        <span class="fw-bold text-success">Rs {{ number_format((int) ($rev['markup_revenue'] ?? 0)) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 mt-2">
                        <span class="text-secondary">Gross sales</span>
                        <span class="fw-bold">Rs {{ number_format((int) ($s['gross_sales'] ?? 0)) }}</span>
                    </div>
                    <div class="text-secondary small mt-2">Based on live booking records.</div>
                </div>
            </div>

            {{-- PART E: Supplier health --}}
            <div class="card mb-4" data-testid="ota-supplier-health">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Supplier health</h3>
                    <a href="{{ route('admin.api-settings') }}" class="btn btn-sm btn-outline-primary">
                        Manage API settings
                    </a>
                </div>
                <div class="card-body">
                    @forelse ($health as $h)
                        @php
                            $statusKey = (string) ($h['status'] ?? 'not_configured');
                            $manageRoute = (string) ($h['manage_route'] ?? 'admin.api-settings');
                            $manageParams = (array) ($h['manage_route_params'] ?? []);
                        @endphp
                        <div class="ota-prov-card" data-testid="ota-supplier-health-{{ strtolower((string) ($h['code'] ?? '')) }}">
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-semibold">{{ $h['name'] }}</span>
                                    <span class="ota-prov-status ota-prov-status--{{ $statusKey }}">{{ $h['status_label'] }}</span>
                                </div>
                                <div class="ota-prov-meta">
                                    @if (($h['last_search_at'] ?? null))
                                        <span>Last search {{ $h['last_search_at'] }}</span>
                                    @endif
                                    @if (($h['last_validation_at'] ?? null))
                                        <span class="ms-2">Validated {{ $h['last_validation_at'] }}</span>
                                    @endif
                                    @if (! ($h['last_search_at'] ?? null) && ! ($h['last_validation_at'] ?? null))
                                        <span>{{ $h['detail'] ?? '' }}</span>
                                    @endif
                                </div>
                                @if (($h['last_error'] ?? null))
                                    <div class="ota-prov-error" title="Last error">
                                        <i class="ti ti-alert-circle me-1"></i>{{ \Illuminate\Support\Str::limit($h['last_error'], 120) }}
                                    </div>
                                @endif
                            </div>
                            <div class="text-end">
                                <a href="{{ route($manageRoute, $manageParams) }}" class="btn btn-sm btn-link p-0">
                                    {{ $statusKey === 'not_configured' ? 'Set up' : 'Manage' }}
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-secondary small">No supplier providers configured.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
