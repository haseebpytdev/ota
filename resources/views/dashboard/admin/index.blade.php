@extends('layouts.dashboard')

@section('title', 'Admin')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Overview</div>
            <h1 class="page-title">{{ $role }} dashboard</h1>
            <div class="text-secondary mt-1">
                Booking KPIs and operations are powered by live database records scoped by agency.
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
    @endphp
    @if (! $hasLiveData)
        <div class="alert alert-info mb-3">
            <i class="ti ti-info-circle me-1"></i>
            No live booking data yet. Metrics update automatically once bookings are created.
        </div>
    @endif
    <div class="card ota-admin-welcome ota-admin-welcome--compact mb-3">
        <div class="card-body ota-admin-welcome-body">
            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                <div class="avatar avatar-sm rounded bg-primary text-white ota-welcome-avatar">
                    <i class="ti ti-plane-inflight"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <h2 class="card-title ota-admin-welcome-title mb-0">Welcome to the operator console</h2>
                    <p class="text-secondary small mb-0 mt-1 ota-admin-welcome-lead">
                        Manage bookings, partners, users, pricing rules, supplier settings, and reporting in one workspace.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <h3 class="h5 fw-bold mb-3 ota-monthly-overview-head">Monthly overview</h3>
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm ota-kpi-card">
                <div class="card-body">
                    <div class="text-secondary">Total bookings</div>
                    <div class="h2 mb-0">{{ number_format($s['total_bookings'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm ota-kpi-card ota-kpi-accent-amber">
                <div class="card-body">
                    <div class="text-secondary">Pending bookings</div>
                    <div class="h2 mb-0">{{ number_format($s['pending_bookings'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm ota-kpi-card ota-kpi-accent-emerald">
                <div class="card-body">
                    <div class="text-secondary">Ticketed bookings</div>
                    <div class="h2 mb-0">{{ number_format($s['ticketed_bookings'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm ota-kpi-card ota-kpi-accent-violet">
                <div class="card-body">
                    <div class="text-secondary">Unpaid/partial bookings</div>
                    <div class="h2 mb-0">{{ number_format($s['unpaid_partial_bookings'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-2">
        <h3 class="h5 fw-bold mb-3">Quick actions</h3>
        <div class="row g-3 ota-admin-quick">
            <div class="col-6 col-lg-3">
                <a href="{{ route('admin.bookings') }}" class="ota-quick-action-link text-reset text-decoration-none">
                    <div class="card h-100 ota-quick-action-card">
                        <div class="card-body">
                            <div class="ota-quick-icon"><i class="ti ti-ticket"></i></div>
                            <div class="fw-bold">Manage bookings</div>
                            <div class="text-secondary small mt-1">Pipeline &amp; previews</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="{{ route('admin.agents') }}" class="ota-quick-action-link text-reset text-decoration-none">
                    <div class="card h-100 ota-quick-action-card">
                        <div class="card-body">
                            <div class="ota-quick-icon"><i class="ti ti-users"></i></div>
                            <div class="fw-bold">Add agent</div>
                            <div class="text-secondary small mt-1">Onboard &amp; commission profile</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="{{ route('admin.api-settings') }}" class="ota-quick-action-link text-reset text-decoration-none">
                    <div class="card h-100 ota-quick-action-card">
                        <div class="card-body">
                            <div class="ota-quick-icon"><i class="ti ti-api"></i></div>
                            <div class="fw-bold">API settings</div>
                            <div class="text-secondary small mt-1">Suppliers &amp; keys</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="{{ route('admin.reports') }}" class="ota-quick-action-link text-reset text-decoration-none">
                    <div class="card h-100 ota-quick-action-card">
                        <div class="card-body">
                            <div class="ota-quick-icon"><i class="ti ti-chart-bar"></i></div>
                            <div class="fw-bold">View reports</div>
                            <div class="text-secondary small mt-1">Sales &amp; mix</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <h3 class="h5 fw-bold mb-3">Today's operations</h3>
    <div class="row g-3 mb-4">
        @foreach ($ops as $op)
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route($op['route'] ?? 'admin.bookings') }}" class="text-reset text-decoration-none">
                    <div class="card h-100 border">
                        <div class="card-body">
                            <div class="text-secondary text-uppercase small fw-bold" style="font-size:0.65rem;letter-spacing:.06em;">{{ $op['title'] ?? '' }}</div>
                            <div class="h2 mb-0">{{ number_format((int) ($op['count'] ?? 0)) }}</div>
                            <div class="text-secondary small mt-1">{{ $op['hint'] ?? '' }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <h3 class="h5 fw-bold mb-1">Supplier readiness</h3>
            <p class="text-secondary small mb-3">Configuration readiness only. Live supplier operations are pending supplier CRUD and integrations.</p>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($suppliers as $sup)
                                @php
                                    $code = strtoupper(trim((string) ($sup['code'] ?? '')));
                                    $st = strtolower(trim((string) ($sup['readiness'] ?? '')));

                                    if ($code === 'MOCK' && $st === 'live') {
                                        $statusLabel = 'Configured';
                                        $statusClass = 'ota-supplier-status--configured';
                                    } elseif ($st === '') {
                                        $statusLabel = 'Unspecified';
                                        $statusClass = 'ota-supplier-status--unknown';
                                    } else {
                                        [$statusLabel, $statusClass] = match ($st) {
                                            'connected' => ['Connected', 'ota-supplier-status--connected'],
                                            'live' => ['Live', 'ota-supplier-status--live'],
                                            'pending' => ['Pending', 'ota-supplier-status--pending'],
                                            'optional', 'not_configured', 'not configured' => ['Not configured', 'ota-supplier-status--not-configured'],
                                            'demo' => ['Configured', 'ota-supplier-status--configured'],
                                            default => ['Unspecified', 'ota-supplier-status--unknown'],
                                        };
                                    }
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $sup['name'] ?? '' }} <span class="text-secondary small">({{ $sup['code'] ?? '' }})</span></td>
                                    <td><span class="ota-supplier-status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td class="text-secondary small">{{ $sup['detail'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <h3 class="h5 fw-bold mb-3">Revenue snapshot</h3>
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-3">{{ $rev['period_label'] ?? '' }}</p>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Direct customer sales</span>
                        <span class="fw-bold">Rs {{ number_format((int) ($rev['direct_customer_sales'] ?? 0), 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Agent sales</span>
                        <span class="fw-bold">Rs {{ number_format((int) ($rev['agent_sales'] ?? 0), 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-secondary">Markup revenue</span>
                        <span class="fw-bold text-success">Rs {{ number_format((int) ($rev['markup_revenue'] ?? 0), 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-top mt-2">
                        <span class="text-secondary">Gross sales</span>
                        <span class="fw-bold">Rs {{ number_format((int) ($s['gross_sales'] ?? 0), 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm ota-recent-bookings-card mt-4">
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
                        <th>Ref</th>
                        <th>Route</th>
                        <th>Customer/contact</th>
                        <th>Airline</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-end">Amount (PKR)</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $row)
                        @php
                            $st = strtolower((string) ($row['status'] ?? ''));
                            $bcls = match (true) {
                                str_contains($st, 'cancel') => 'ota-bstat ota-bstat--cancelled',
                                str_contains($st, 'ticket') => 'ota-bstat ota-bstat--ticketed',
                                str_contains($st, 'pending') => 'ota-bstat ota-bstat--pending',
                                str_contains($st, 'confirm') => 'ota-bstat ota-bstat--confirmed',
                                default => 'ota-bstat ota-bstat--muted',
                            };
                        @endphp
                        <tr>
                            <td class="fw-semibold text-secondary">{{ $row['ref'] }}</td>
                            <td>{{ $row['route'] }}</td>
                            <td>{{ $row['customer'] }}</td>
                            <td>{{ $row['airline'] }}</td>
                            <td><span class="{{ $bcls }}">{{ $row['status'] }}</span></td>
                            <td class="text-capitalize">{{ $row['payment_status'] }}</td>
                            <td class="text-end fw-semibold">Rs {{ number_format((float) $row['amount_pkr'], 0) }}</td>
                            <td class="text-secondary small">{{ $row['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">No bookings to display yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-top-0 pt-0">
            <a href="{{ route('admin.bookings') }}" class="btn btn-primary">Open bookings module</a>
        </div>
    </div>
@endsection
