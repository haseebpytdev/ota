@extends('layouts.dashboard')

@section('title', 'Supplier Diagnostics')

@push('styles')
<style>
    .ota-diag-toolbar {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        border: 1px solid rgba(98, 105, 118, 0.16);
        border-radius: 14px;
        padding: 16px 22px;
        box-shadow: 0 6px 22px rgba(15, 23, 42, 0.05);
    }
    .ota-diag-card {
        border: 1px solid rgba(98, 105, 118, 0.16);
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .ota-diag-table-wrapper {
        overflow: auto;
        max-height: 640px;
    }
    .ota-diag-table {
        min-width: 1120px;
        margin-bottom: 0;
    }
    .ota-diag-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8fafc;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: var(--tblr-secondary, #62748e);
        border-bottom: 2px solid rgba(98, 105, 118, 0.18);
        padding: 0.75rem 0.9rem;
        white-space: nowrap;
    }
    .ota-diag-table tbody td {
        padding: 0.85rem 0.9rem;
        vertical-align: top;
        border-top: 1px solid rgba(98, 105, 118, 0.08);
    }
    .ota-diag-table tbody tr:hover { background-color: rgba(37, 99, 235, 0.045); }
    .ota-diag-message {
        max-width: 340px;
        white-space: normal;
        word-break: break-word;
    }
    .ota-diag-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.18rem 0.5rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        border: 1px solid rgba(98, 105, 118, 0.18);
        background: #f8fafc;
    }
    .ota-diag-chip--ok { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
    .ota-diag-chip--failed,
    .ota-diag-chip--error { background: #fee2e2; color: #7f1d1d; border-color: #fca5a5; }
    .ota-duffel-error {
        border: 1px solid rgba(98, 105, 118, 0.14);
        border-radius: 8px;
        padding: 0.55rem 0.65rem;
        background: #fff;
    }
    .ota-duffel-error + .ota-duffel-error { margin-top: 0.5rem; }
    @media (max-width: 640px) {
        .ota-diag-toolbar { padding: 14px; }
        .ota-diag-toolbar .btn { width: 100%; }
        .ota-diag-filter > [class*="col-"] { width: 100%; }
    }
</style>
@endpush

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Reports</div>
            <h1 class="page-title">Supplier Diagnostics</h1>
            <div class="text-secondary mt-1">
                Safe supplier error drilldown scoped to your agency.
            </div>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.reports', ['tab' => 'suppliers']) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to supplier report
            </a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $f = $filters ?? [];
    @endphp

    <form method="GET" action="{{ route('admin.reports.supplier-diagnostics') }}" class="ota-diag-toolbar mb-4" data-testid="ota-supplier-diagnostics-filters">
        <div class="row g-2 align-items-end ota-diag-filter">
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1">Provider</label>
                <select name="provider" class="form-select form-select-sm">
                    <option value="all" @selected(($f['provider'] ?? 'all') === 'all')>All providers</option>
                    @foreach ($providerOptions as $provider)
                        <option value="{{ $provider }}" @selected(($f['provider'] ?? '') === $provider)>{{ ucwords(str_replace('_', ' ', $provider)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="" @selected(($f['action'] ?? '') === '')>All actions</option>
                    @foreach ($actionOptions as $action)
                        <option value="{{ $action }}" @selected(($f['action'] ?? '') === $action)>{{ ucwords(str_replace('_', ' ', $action)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected(($f['status'] ?? 'errors') === $status)>{{ $status === 'errors' ? 'Errors only' : ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $f['date_from'] ?? '' }}">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $f['date_to'] ?? '' }}">
            </div>
            <div class="col-12 col-md-3 col-xl-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="ti ti-filter me-1"></i> Apply filters
                </button>
            </div>
        </div>
    </form>

    <div class="ota-diag-card" data-testid="ota-supplier-diagnostics-page">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h3 class="card-title mb-0">Latest diagnostics</h3>
                <div class="text-secondary small mt-1">Only safe fields are displayed. Raw payloads, credentials, tokens, and passenger documents are not rendered.</div>
            </div>
            <span class="badge bg-blue-lt">{{ number_format($diagnostics->count()) }} rows</span>
        </div>
        <div class="ota-diag-table-wrapper">
            <table class="table table-vcenter card-table ota-diag-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Provider</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>Reason code</th>
                        <th>Error code</th>
                        <th>HTTP</th>
                        <th>Endpoint</th>
                        <th>Safe message</th>
                        <th>Last error detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($diagnostics as $row)
                        <tr data-testid="ota-diagnostic-row">
                            <td class="text-secondary small text-nowrap">{{ $row['created_at'] }}</td>
                            <td class="fw-semibold">{{ ucwords(str_replace('_', ' ', $row['provider'])) }}</td>
                            <td>{{ $row['action'] ?: '—' }}</td>
                            <td><span class="ota-diag-chip ota-diag-chip--{{ $row['status'] }}">{{ $row['status'] ?: '—' }}</span></td>
                            <td>{{ $row['reason_code'] ?: '—' }}</td>
                            <td>{{ $row['error_code'] ?: '—' }}</td>
                            <td>{{ $row['http_status'] ?: '—' }}</td>
                            <td class="small text-secondary">{{ $row['endpoint'] ?: '—' }}</td>
                            <td class="ota-diag-message">{{ $row['safe_message'] ?: '—' }}</td>
                            <td class="ota-diag-message">
                                @forelse ($row['duffel_errors'] as $error)
                                    <div class="ota-duffel-error" data-testid="ota-duffel-error">
                                        <div class="fw-semibold">{{ $error['code'] ?: 'Duffel error' }}{{ $error['title'] ? ' · '.$error['title'] : '' }}</div>
                                        @if ($error['detail'])
                                            <div class="small mt-1">{{ $error['detail'] }}</div>
                                        @endif
                                        @if ($error['source_pointer'])
                                            <div class="small text-secondary mt-1">Source: {{ $error['source_pointer'] }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-secondary">—</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-secondary py-5">
                                No supplier diagnostics match these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
