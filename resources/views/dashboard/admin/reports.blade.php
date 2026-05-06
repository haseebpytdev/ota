@extends('layouts.dashboard')

@section('title', 'Reports')

@push('styles')
<style>
    .reports-kpi .card { border: 1px solid rgba(98,105,118,.16); }
    .reports-filters { background: var(--tblr-bg-surface, #f8fafc); border-radius: 8px; padding: 1rem 1.25rem; border: 1px solid rgba(98,105,118,.12); }
    .reports-table-wrap { border-radius: 8px; overflow: hidden; border: 1px solid rgba(98,105,118,.12); }
    .reports-table-wrap .card { border: 0; box-shadow: none; }
</style>
@endpush

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Analytics</div>
            <h1 class="page-title">Reports</h1>
            <div class="text-secondary mt-1">
                Live booking analytics scoped by agency.
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $sum = $summary ?? [];
        $f = $filters ?? [];
        $hasLiveData = (bool) ($hasLiveData ?? false);
    @endphp

    @if ($hasLiveData)
        <div class="alert alert-success mb-4">
            <i class="ti ti-database me-2"></i>Using live booking data from the database.
        </div>
    @else
        <div class="alert alert-info mb-4">
            <i class="ti ti-info-circle me-2"></i>No live booking data yet for the selected filters.
        </div>
    @endif

    <div class="row row-cards reports-kpi mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Gross sales</div>
                    <div class="h2 mb-0">Rs {{ number_format((int) ($sum['gross_sales'] ?? 0), 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Net revenue</div>
                    <div class="h2 mb-0 text-success">Rs {{ number_format((int) ($sum['net_revenue'] ?? 0), 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Total bookings</div>
                    <div class="h2 mb-0">{{ number_format((int) ($sum['total_bookings'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Agent sales</div>
                    <div class="h2 mb-0">Rs {{ number_format((int) ($sum['agent_sales'] ?? 0), 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Approved commission</div>
                    <div class="h2 mb-0">Rs {{ number_format((float) ($commissionTotals['approved'] ?? 0), 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Paid commission</div>
                    <div class="h2 mb-0">Rs {{ number_format((float) ($commissionTotals['paid'] ?? 0), 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Cancellations</div>
                    <div class="h2 mb-0">{{ number_format((int) ($sum['cancellation_count'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Refund paid</div>
                    <div class="h2 mb-0">Rs {{ number_format((float) ($sum['refund_paid_amount'] ?? 0), 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Pending refunds</div>
                    <div class="h2 mb-0">{{ number_format((int) ($sum['pending_refund_count'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.reports') }}" class="reports-filters mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $f['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $f['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label">Channel</label>
                <select name="channel" class="form-select">
                    <option value="all" @selected(($f['channel'] ?? 'all') === 'all')>All</option>
                    <option value="direct" @selected(($f['channel'] ?? '') === 'direct')>Direct customer</option>
                    <option value="agent" @selected(($f['channel'] ?? '') === 'agent')>Agent</option>
                </select>
            </div>
            <div class="col-md-3 col-lg-3">
                <label class="form-label">Supplier</label>
                <select name="supplier" class="form-select">
                    <option value="all" @selected(($f['supplier'] ?? 'all') === 'all')>All</option>
                    <option value="mock" @selected(($f['supplier'] ?? '') === 'mock')>Provider not connected</option>
                    <option value="sabre" @selected(($f['supplier'] ?? '') === 'sabre')>Sabre</option>
                    <option value="pia" @selected(($f['supplier'] ?? '') === 'pia')>PIA</option>
                    <option value="airline_direct" @selected(($f['supplier'] ?? '') === 'airline_direct')>Airline direct</option>
                </select>
            </div>
            <div class="col-md-12 col-lg-3">
                <button type="submit" class="btn btn-primary w-100">Apply filters</button>
            </div>
        </div>
    </form>

    <div class="row g-4">
        <div class="col-12">
            <div class="card reports-table-wrap mb-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title">Monthly sales</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Bookings</th>
                                <th class="text-end">Gross sales</th>
                                <th class="text-end">Net revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($monthlySales as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['month'] ?? '—' }}</td>
                                    <td class="text-end">{{ number_format((int) ($row['bookings'] ?? 0)) }}</td>
                                    <td class="text-end">Rs {{ number_format((int) ($row['gross_sales'] ?? 0), 0) }}</td>
                                    <td class="text-end">Rs {{ number_format((int) ($row['net_revenue'] ?? 0), 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-4">No live booking data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card reports-table-wrap mb-4 mb-lg-0">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title">Top routes</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Route</th>
                                <th class="text-end">Bookings</th>
                                <th class="text-end">Sales</th>
                                <th class="text-end">Average ticket</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topRoutes as $row)
                                <tr>
                                    <td>{{ $row['route'] ?? '—' }}</td>
                                    <td class="text-end">{{ number_format((int) ($row['bookings'] ?? 0)) }}</td>
                                    <td class="text-end">Rs {{ number_format((int) ($row['sales'] ?? 0), 0) }}</td>
                                    <td class="text-end">Rs {{ number_format((int) ($row['average_ticket'] ?? 0), 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-4">No live booking data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card reports-table-wrap mb-4 mb-lg-0">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title">Top agents</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Agent code</th>
                                <th>Agent</th>
                                <th class="text-end">Bookings</th>
                                <th class="text-end">Sales</th>
                                <th class="text-end">Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topAgents as $row)
                                <tr>
                                    <td class="fw-semibold text-nowrap">{{ $row['agent_code'] ?? '—' }}</td>
                                    <td class="small">{{ $row['agent_name'] ?? '—' }}</td>
                                    <td class="text-end">{{ number_format((int) ($row['bookings'] ?? 0)) }}</td>
                                    <td class="text-end">Rs {{ number_format((int) ($row['sales'] ?? 0), 0) }}</td>
                                    <td class="text-end">Rs {{ number_format((int) ($row['commission'] ?? 0), 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-4">No live booking data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card reports-table-wrap">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title">Payment breakdown</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payment status</th>
                                <th class="text-end">Count</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paymentBreakdown as $row)
                                <tr>
                                    <td>{{ $row['status'] ?? '—' }}</td>
                                    <td class="text-end">{{ number_format((int) ($row['count'] ?? 0)) }}</td>
                                    <td class="text-end">Rs {{ number_format((int) ($row['amount'] ?? 0), 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-secondary py-4">No live booking data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
