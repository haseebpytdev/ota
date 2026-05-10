@extends('layouts.dashboard')

@section('title', 'Staff')

@push('styles')
<style>
    .staff-kpi .card { border: 1px solid rgba(98,105,118,.16); }
    .staff-filters { background: var(--tblr-bg-surface, #f8fafc); border-radius: 8px; padding: 1rem 1.25rem; border: 1px solid rgba(98,105,118,.12); }
    .staff-table-wrap { border-radius: 8px; overflow: hidden; border: 1px solid rgba(98,105,118,.12); }
    .staff-preview { position: sticky; top: 1rem; }
    .staff-preview .card { border: 1px solid rgba(98,105,118,.16); box-shadow: 0 4px 24px rgba(15,23,42,.06); }
    .staff-preview h4 { font-size: 1rem; font-weight: 600; margin-bottom: .75rem; }
    .metric-row { display: flex; justify-content: space-between; padding: .4rem 0; border-bottom: 1px dashed rgba(98,105,118,.2); font-size: .875rem; }
    .metric-row:last-of-type { border-bottom: none; font-weight: 600; }
</style>
@endpush

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Operations</div>
            <h1 class="page-title">Staff management</h1>
            <div class="text-secondary mt-1">
                Staff profiles, access status, and assignment visibility from live records.
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php $s = $selectedStaff; @endphp

    <div class="row row-cards staff-kpi mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Total staff</div>
                    <div class="h2 mb-0">{{ number_format($kpis['total'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Active staff</div>
                    <div class="h2 mb-0 text-success">{{ number_format($kpis['active'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Inactive/suspended</div>
                    <div class="h2 mb-0 text-warning">{{ number_format($kpis['inactive'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary small">Assigned bookings</div>
                    <div class="h2 mb-0">{{ number_format($kpis['assigned_bookings'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="staff-filters mb-4">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" placeholder="Name, email, department, job title" value="{{ $filters['search'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department">
                            <option>All departments</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept }}" @selected(($filters['department'] ?? '') === $dept)>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All statuses</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                            <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Suspended</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                    </div>
                </form>
            </div>

            <div class="card staff-table-wrap">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title">Staff</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff code</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Job title</th>
                                <th class="text-end">Assigned bookings</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staff as $row)
                                @php
                                    $st = $row['status'] ?? 'inactive';
                                    $stBadge = match ($st) {
                                        'active' => 'bg-success',
                                        'suspended' => 'bg-warning',
                                        default => 'bg-secondary',
                                    };
                                    $stLabel = ucfirst(str_replace('_', ' ', $st));
                                    $isSel = $s && (($row['staff_code'] ?? null) === ($s['staff_code'] ?? null));
                                @endphp
                                <tr class="{{ $isSel ? 'table-primary' : '' }}">
                                    <td class="fw-semibold text-nowrap">{{ $row['staff_code'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="small">{{ $row['email'] }}</td>
                                    <td>{{ $row['department'] }}</td>
                                    <td class="small">{{ $row['job_title'] }}</td>
                                    <td class="text-end">{{ number_format((int) ($row['assigned_bookings'] ?? 0)) }}</td>
                                    <td><span class="badge {{ $stBadge }}">{{ $stLabel }}</span></td>
                                    <td>
                                        <a href="{{ route('admin.staff', ['preview' => $row['id']]) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-secondary py-4">
                                        No staff users have been created yet. Create staff from Users &amp; Access.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="staff-preview">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Staff preview</h3>
                        <div class="card-subtitle text-secondary">
                            @if ($previewCode !== '')
                                <code>{{ $previewCode }}</code>
                            @else
                                Default — first staff member. Use <strong>View</strong> to switch.
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($s)
                            <h4>Staff</h4>
                            <p class="fw-semibold mb-1">{{ $s['name'] }}</p>
                            <p class="small text-secondary mb-1">{{ $s['staff_code'] }} · {{ $s['job_title'] }}</p>
                            <p class="small text-secondary mb-3">{{ $s['department'] }}</p>

                            <h4>Contact</h4>
                            <ul class="list-unstyled small mb-3">
                                <li><i class="ti ti-mail me-1"></i> {{ $s['email'] }}</li>
                            </ul>

                            <h4>Workload &amp; activity</h4>
                            <div class="mb-3">
                                <div class="metric-row"><span>Assigned bookings</span><span>{{ number_format((int) ($s['assigned_bookings'] ?? 0)) }}</span></div>
                                <div class="metric-row"><span>Status</span><span>{{ ucfirst((string) ($s['status'] ?? 'inactive')) }}</span></div>
                                <div class="metric-row"><span>Last login</span><span>{{ $s['last_login_at'] ?? 'Never' }}</span></div>
                            </div>

                            <h4>Recent assigned bookings</h4>
                            <ul class="small mb-3">
                                @forelse(($s['recent_bookings'] ?? []) as $booking)
                                    <li>{{ $booking['reference'] }} · {{ $booking['route'] }} · {{ ucfirst(str_replace('_', ' ', $booking['status'])) }}</li>
                                @empty
                                    <li class="text-secondary">No assigned bookings yet.</li>
                                @endforelse
                            </ul>
                        @else
                            <p class="text-secondary mb-0">No staff member selected.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
