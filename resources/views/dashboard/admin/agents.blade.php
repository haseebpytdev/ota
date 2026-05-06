@extends('layouts.dashboard')

@section('title', 'Agents')

@push('styles')
<style>
    .agents-kpi .card { border: 1px solid rgba(98,105,118,.16); }
    .agents-filters { background: var(--tblr-bg-surface, #f8fafc); border-radius: 8px; padding: 1rem 1.25rem; border: 1px solid rgba(98,105,118,.12); }
    .agents-table-wrap { border-radius: 8px; overflow: hidden; border: 1px solid rgba(98,105,118,.12); }
    .agents-preview { position: sticky; top: 1rem; }
    .agents-preview .card { border: 1px solid rgba(98,105,118,.16); box-shadow: 0 4px 24px rgba(15,23,42,.06); }
    .agents-preview h4 { font-size: 1rem; font-weight: 600; margin-bottom: .75rem; }
    .metric-row { display: flex; justify-content: space-between; padding: .4rem 0; border-bottom: 1px dashed rgba(98,105,118,.2); font-size: .875rem; }
    .metric-row:last-of-type { border-bottom: none; font-weight: 600; }
</style>
@endpush

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Network</div>
            <h1 class="page-title">Agents management</h1>
            <div class="text-secondary mt-1">
                Agent performance and commission visibility from live agency records.
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php $a = $selectedAgent; @endphp
    <div class="row row-cards agents-kpi mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Total agents</div>
                    <div class="h2 mb-0">{{ number_format($kpis['total'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Active agents</div>
                    <div class="h2 mb-0 text-success">{{ number_format($kpis['active'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Monthly sales</div>
                    <div class="h2 mb-0">Rs {{ number_format($kpis['monthly_sales'] ?? 0, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Pending commission balance</div>
                    <div class="h2 mb-0 text-warning">Rs {{ number_format($kpis['outstanding'] ?? 0, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Inactive agents</div>
                    <div class="h2 mb-0 text-secondary">{{ number_format($kpis['inactive'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="agents-filters mb-4">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Code, agency, contact, email, phone" value="{{ $filters['search'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">City</label>
                        <select class="form-select" name="city">
                            <option>All cities</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All statuses</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                    </div>
                </form>
            </div>

            <div class="card agents-table-wrap">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title">Agents</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Agent code</th>
                                <th>Agency</th>
                                <th>Contact person</th>
                                <th>User email</th>
                                <th>City</th>
                                <th>Commission</th>
                                <th class="text-end">Bookings</th>
                                <th class="text-end">Monthly sales</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agents as $row)
                                @php
                                    $st = $row['status'] ?? 'pending';
                                    $stBadge = match ($st) {
                                        'active' => 'bg-success',
                                        default => 'bg-secondary',
                                    };
                                    $isSel = $a && (($row['agent_code'] ?? null) === ($a['agent_code'] ?? null));
                                @endphp
                                <tr class="{{ $isSel ? 'table-primary' : '' }}">
                                    <td class="fw-semibold text-nowrap">{{ $row['agent_code'] }}</td>
                                    <td>{{ $row['agency_name'] }}</td>
                                    <td>{{ $row['contact_person'] }}</td>
                                    <td>{{ $row['email'] }}</td>
                                    <td>{{ $row['city'] }}</td>
                                    <td class="small">{{ number_format((float) ($row['commission_percent'] ?? 0), 2) }}%</td>
                                    <td class="text-end">{{ number_format((int) ($row['bookings_count'] ?? 0)) }}</td>
                                    <td class="text-end">Rs {{ number_format((int) ($row['monthly_sales'] ?? 0), 0) }}</td>
                                    <td class="text-end">Rs {{ number_format((int) ($row['outstanding_balance'] ?? 0), 0) }}</td>
                                    <td><span class="badge {{ $stBadge }}">{{ ucfirst($st) }}</span></td>
                                    <td>
                                        <a href="{{ route('admin.agents', ['preview' => $row['id']]) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-secondary py-4">
                                        No agents have been created yet. Create agents from Users &amp; Access or Agent module.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="agents-preview">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Agent preview</h3>
                        <div class="card-subtitle text-secondary">
                            @if ($previewCode !== '')
                                <code>{{ $previewCode }}</code>
                            @else
                                Default — first agent. Use <strong>View</strong> to switch.
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($a)
                            <h4>Agency</h4>
                            <p class="fw-semibold mb-1">{{ $a['agency_name'] }}</p>
                            <p class="small text-secondary mb-3">{{ $a['agent_code'] }} · {{ $a['city'] }}</p>

                            <h4>Contact</h4>
                            <ul class="list-unstyled small mb-3">
                                <li><i class="ti ti-user me-1"></i> {{ $a['contact_person'] }}</li>
                                <li><i class="ti ti-phone me-1"></i> {{ $a['phone'] }}</li>
                                <li><i class="ti ti-mail me-1"></i> {{ $a['email'] }}</li>
                            </ul>

                            <h4>Commission</h4>
                            <p class="small mb-2">{{ number_format((float) ($a['commission_percent'] ?? 0), 2) }}%</p>
                            <p class="mb-3"><span class="badge bg-secondary">Entries: {{ number_format((int) ($a['commission_entries_count'] ?? 0)) }}</span></p>

                            <h4>Performance</h4>
                            <div class="mb-3">
                                <div class="metric-row"><span>Total bookings</span><span>{{ number_format((int) ($a['bookings_count'] ?? 0)) }}</span></div>
                                <div class="metric-row"><span>Monthly sales</span><span>Rs {{ number_format((int) ($a['monthly_sales'] ?? 0), 0) }}</span></div>
                                <div class="metric-row"><span>Balance</span><span>Rs {{ number_format((int) ($a['outstanding_balance'] ?? 0), 0) }}</span></div>
                                <div class="metric-row"><span>Status</span><span>{{ ucfirst((string) ($a['status'] ?? 'inactive')) }}</span></div>
                            </div>

                            <h4>Recent bookings</h4>
                            <ul class="small mb-3">
                                @forelse(($a['recent_bookings'] ?? []) as $booking)
                                    <li>{{ $booking['reference'] }} · {{ $booking['route'] }} · {{ ucfirst(str_replace('_', ' ', $booking['status'])) }}</li>
                                @empty
                                    <li class="text-secondary">No bookings yet.</li>
                                @endforelse
                            </ul>

                            <div class="alert alert-secondary small mb-3">
                                <strong>Notes</strong><br>{{ $a['notes'] ?? '—' }}
                            </div>
                        @else
                            <p class="text-secondary mb-0">No agent selected.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
