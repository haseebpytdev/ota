@extends('layouts.dashboard')

@section('title', 'Markup Rules')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Pricing Control</div>
            <h1 class="page-title">Markup rules</h1>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('admin.markups.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Create rule
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="alert alert-warning mb-3">
        Changing markup rules affects newly created bookings only. Existing booking fare snapshots are preserved.
    </div>

    @php($k = $kpis ?? [])
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3"><div class="card card-sm ota-kpi-card"><div class="card-body"><div class="text-secondary">Active rules</div><div class="h2 mb-0">{{ number_format((int) ($k['active'] ?? 0)) }}</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card card-sm ota-kpi-card ota-kpi-accent-amber"><div class="card-body"><div class="text-secondary">Route rules</div><div class="h2 mb-0">{{ number_format((int) ($k['route'] ?? 0)) }}</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card card-sm ota-kpi-card ota-kpi-accent-emerald"><div class="card-body"><div class="text-secondary">Airline rules</div><div class="h2 mb-0">{{ number_format((int) ($k['airline'] ?? 0)) }}</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card card-sm ota-kpi-card ota-kpi-accent-violet"><div class="card-body"><div class="text-secondary">Agent rules</div><div class="h2 mb-0">{{ number_format((int) ($k['agent'] ?? 0)) }}</div></div></div></div>
    </div>

    @php($f = $filters ?? [])
    <form method="GET" action="{{ route('admin.markups') }}" class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Rule type</label>
                    <select name="type" class="form-select">
                        <option value="">All</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(($f['type'] ?? '') === $type->value)>{{ str_replace('_', ' ', $type->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($f['status'] ?? '') === $status->value)>{{ ucfirst($status->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100">Apply filters</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table ota-admin-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Applies to</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Active window</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td class="fw-semibold">{{ $rule->name }}</td>
                            <td class="text-capitalize">{{ str_replace('_', ' ', $rule->rule_type->value) }}</td>
                            <td>
                                @if ($rule->value_type->value === 'percentage')
                                    {{ number_format((float) $rule->value, 2) }}%
                                @else
                                    Rs {{ number_format((float) $rule->value, 0) }}
                                @endif
                            </td>
                            <td class="small text-secondary">{{ $rule->applies_to ? json_encode($rule->applies_to) : '—' }}</td>
                            <td>{{ $rule->priority }}</td>
                            <td><span class="badge {{ $rule->status->value === 'active' ? 'bg-success' : ($rule->status->value === 'draft' ? 'bg-warning' : 'bg-secondary') }}">{{ ucfirst($rule->status->value) }}</span></td>
                            <td class="small">
                                {{ $rule->starts_at?->format('Y-m-d') ?? '—' }}
                                to
                                {{ $rule->ends_at?->format('Y-m-d') ?? '—' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.markups.edit', $rule) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @can('update', $rule)
                                    <form method="POST" action="{{ route('admin.markups.toggle-status', $rule) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Toggle</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">No markup rules yet. Default pricing will apply until rules are added.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $rules->links() }}</div>
    </div>
@endsection
