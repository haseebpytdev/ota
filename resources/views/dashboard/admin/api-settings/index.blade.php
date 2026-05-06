@extends('layouts.dashboard')

@section('title', 'API Settings')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Integrations</div>
            <h1 class="page-title">Supplier API settings</h1>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('admin.api-settings.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Add connection
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="alert alert-warning mb-3">
        Credentials are encrypted and never displayed after saving. Add Duffel test access token from Duffel dashboard and keep environment sandbox/test.
    </div>
    @if (!($activeRealSupplierExists ?? false))
        <div class="alert alert-info mb-3">
            No active supplier is connected. Flight search may use fallback provider if enabled.
        </div>
    @endif

    @php($k = $kpis ?? [])
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3"><div class="card card-sm ota-kpi-card"><div class="card-body"><div class="text-secondary">Total suppliers</div><div class="h2 mb-0">{{ number_format((int) ($k['total'] ?? 0)) }}</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card card-sm ota-kpi-card ota-kpi-accent-emerald"><div class="card-body"><div class="text-secondary">Active suppliers</div><div class="h2 mb-0">{{ number_format((int) ($k['active'] ?? 0)) }}</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card card-sm ota-kpi-card ota-kpi-accent-amber"><div class="card-body"><div class="text-secondary">Sandbox</div><div class="h2 mb-0">{{ number_format((int) ($k['sandbox'] ?? 0)) }}</div></div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="card card-sm ota-kpi-card ota-kpi-accent-violet"><div class="card-body"><div class="text-secondary">Live</div><div class="h2 mb-0">{{ number_format((int) ($k['live'] ?? 0)) }}</div></div></div></div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table ota-admin-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Provider</th>
                        <th>Name</th>
                        <th>Environment</th>
                        <th>Status</th>
                        <th>Credentials</th>
                        <th>API Version</th>
                        <th>Last tested</th>
                        <th>Last test status</th>
                        <th>Last error</th>
                        <th>Last search success</th>
                        <th>Last order success</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($connections as $connection)
                        <tr>
                            <td class="text-capitalize">{{ str_replace('_', ' ', $connection->provider->value) }}</td>
                            <td class="fw-semibold">{{ $connection->name }}</td>
                            <td class="text-capitalize">{{ $connection->environment->value }}</td>
                            <td>
                                <span class="badge {{ $connection->status->value === 'active' ? 'bg-success' : ($connection->status->value === 'error' ? 'bg-danger' : 'bg-secondary') }}">
                                    {{ $connection->status->value === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                                @if($connection->provider->value === 'duffel')
                                    <span class="badge bg-azure-lt text-azure ms-1">Duffel</span>
                                @endif
                            </td>
                            <td>
                                @if($connection->provider->value === 'duffel')
                                    @if(!empty($connection->credentials['access_token'] ?? null))
                                        <span class="text-success">Access token configured</span>
                                    @else
                                        <span class="text-danger">Missing token</span>
                                    @endif
                                @else
                                    <span class="text-secondary">Configured</span>
                                @endif
                            </td>
                            <td>
                                @if($connection->provider->value === 'duffel')
                                    {{ $connection->credentials['api_version'] ?? 'v2' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $connection->last_tested_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>{{ $connection->last_test_status ?? '—' }}</td>
                            <td>{{ $connection->last_error ?? $connection->latestReadinessDiagnostic?->safe_message ?? '—' }}</td>
                            <td>{{ $connection->latestSearchDiagnostic?->status === 'success' ? $connection->latestSearchDiagnostic?->created_at?->format('Y-m-d H:i') : '—' }}</td>
                            <td>{{ $connection->latestOrderDiagnostic?->status === 'success' ? $connection->latestOrderDiagnostic?->created_at?->format('Y-m-d H:i') : '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.api-settings.edit', $connection) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.api-settings.test', $connection) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Run readiness check</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-secondary py-4">
                                No supplier rows yet. Add your supplier connections to start searching live fares.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $connections->links() }}</div>
    </div>

    @if (! $hasRows)
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Recommended setup</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach ($fallbackSuppliers as $supplier)
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <div class="fw-semibold">{{ $supplier['name'] ?? '' }}</div>
                                <div class="small text-secondary">{{ $supplier['notes'] ?? '' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
