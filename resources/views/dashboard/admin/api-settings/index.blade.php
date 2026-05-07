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

    <div class="row row-cards mb-4">
        @forelse ($connections as $connection)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 ota-supplier-connection-card" data-supplier-card>
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <div class="text-secondary small">Provider</div>
                                <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $connection->provider->value) }}</div>
                            </div>
                            <span class="badge {{ $connection->status->value === 'active' ? 'bg-success' : ($connection->status->value === 'error' ? 'bg-danger' : 'bg-secondary') }}">
                                {{ $connection->status->value === 'active' ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="mb-2 fw-semibold">{{ $connection->name }}</div>
                        @php($envSlug = $connection->environment->value)
                        <div class="small text-secondary mb-3">
                            Environment:
                            <span class="text-dark">
                                @if ($envSlug === 'sandbox')
                                    Sandbox
                                @elseif ($envSlug === 'live')
                                    Live
                                @else
                                    Training
                                @endif
                            </span>
                            @if ($envSlug === 'sandbox')
                                <span class="badge bg-azure-lt text-azure ms-1">Sandbox</span>
                            @elseif ($envSlug === 'live')
                                <span class="badge bg-green-lt text-green ms-1">Live</span>
                            @else
                                <span class="badge bg-secondary-lt text-secondary ms-1">Training</span>
                            @endif
                        </div>
                        <dl class="row small mb-2">
                            <dt class="col-6 text-secondary">Credentials</dt>
                            <dd class="col-6 mb-1 text-end">
                                @if ($connection->provider->value === 'duffel')
                                    @if (! empty($connection->credentials['access_token'] ?? null))
                                        <span class="text-success">Access token configured</span>
                                    @else
                                        <span class="text-danger">Access token missing</span>
                                    @endif
                                @else
                                    <span class="text-secondary">See edit screen</span>
                                @endif
                            </dd>
                            @if ($connection->provider->value === 'duffel')
                                <dt class="col-6 text-secondary">API version</dt>
                                <dd class="col-6 mb-1 text-end">{{ $connection->credentials['api_version'] ?? 'v2' }}</dd>
                            @endif
                            <dt class="col-6 text-secondary">Last readiness</dt>
                            <dd class="col-6 mb-1 text-end">{{ $connection->last_tested_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                            <dt class="col-6 text-secondary">Last readiness status</dt>
                            <dd class="col-6 mb-1 text-end">{{ $connection->last_test_status ?? '—' }}</dd>
                            <dt class="col-6 text-secondary">Last search</dt>
                            <dd class="col-6 mb-1 text-end">{{ $connection->latestSearchDiagnostic?->status === 'success' ? $connection->latestSearchDiagnostic?->created_at?->format('Y-m-d H:i') : '—' }}</dd>
                            <dt class="col-6 text-secondary">Last order</dt>
                            <dd class="col-6 mb-1 text-end">{{ $connection->latestOrderDiagnostic?->status === 'success' ? $connection->latestOrderDiagnostic?->created_at?->format('Y-m-d H:i') : '—' }}</dd>
                        </dl>
                        @if ($connection->last_error || filled($connection->latestReadinessDiagnostic?->safe_message))
                            <details class="mb-3">
                                <summary class="small cursor-pointer text-secondary">Diagnostics</summary>
                                <div class="small text-secondary mt-2 mb-0">{{ $connection->last_error ?? $connection->latestReadinessDiagnostic?->safe_message }}</div>
                            </details>
                        @endif
                        <div class="d-flex flex-wrap gap-2 mt-auto pt-2 border-top">
                            <a href="{{ route('admin.api-settings.edit', $connection) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('admin.api-settings.test', $connection) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Run readiness check</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body text-center text-secondary py-4">
                    No supplier connections yet. Add your supplier connections to start searching fares.
                </div></div>
            </div>
        @endforelse
    </div>
    @if ($connections->hasPages())
        <div class="mb-4">{{ $connections->links() }}</div>
    @endif

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
