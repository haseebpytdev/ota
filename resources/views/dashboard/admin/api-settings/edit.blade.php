@extends('layouts.dashboard')

@section('title', 'Edit Supplier Connection')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Integrations</div>
            <h1 class="page-title">Edit supplier connection</h1>
        </div>
    </div>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Supplier diagnostics</h3></div>
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-md-4"><strong>Provider:</strong> <span class="text-capitalize">{{ str_replace('_', ' ', $connection->provider?->value ?? '—') }}</span></div>
                <div class="col-md-4"><strong>Environment:</strong> <span class="text-capitalize">{{ $connection->environment?->value ?? '—' }}</span></div>
                <div class="col-md-4"><strong>Status:</strong> <span class="text-capitalize">{{ $connection->status?->value ?? '—' }}</span></div>
                <div class="col-md-4"><strong>Last readiness status:</strong> {{ $connection->last_test_status ?? '—' }}</div>
                <div class="col-md-4"><strong>Last tested at:</strong> {{ $connection->last_tested_at?->format('Y-m-d H:i') ?? '—' }}</div>
                <div class="col-md-4"><strong>Last error:</strong> {{ $connection->last_error ?? $connection->latestReadinessDiagnostic?->safe_message ?? '—' }}</div>
                <div class="col-md-4"><strong>Last successful search:</strong> {{ $connection->latestSearchDiagnostic?->status === 'success' ? $connection->latestSearchDiagnostic?->created_at?->format('Y-m-d H:i') : '—' }}</div>
                <div class="col-md-4"><strong>Last successful order:</strong> {{ $connection->latestOrderDiagnostic?->status === 'success' ? $connection->latestOrderDiagnostic?->created_at?->format('Y-m-d H:i') : '—' }}</div>
            </div>
        </div>
    </div>

    @include('dashboard.admin.api-settings.form')
@endsection
