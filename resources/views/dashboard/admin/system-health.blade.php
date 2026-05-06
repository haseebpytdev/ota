@extends('layouts.dashboard')

@section('title', 'System Health')

@section('page-header')
    <h1 class="page-title">System Health</h1>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Diagnostics</h3></div>
        <div class="card-body">
            <div class="row g-2">
                @foreach($checks as $label => $value)
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <div class="small text-secondary">{{ str_replace('_', ' ', $label) }}</div>
                            <div>{{ is_bool($value) ? ($value ? 'OK' : 'FAIL') : $value }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Recent Admin Activity</h3></div>
        <div class="card-body">
            @forelse($recentAdminActivity as $log)
                <div class="small border-bottom py-2">
                    <strong>{{ $log->action }}</strong> · {{ $log->created_at?->format('Y-m-d H:i') }}
                </div>
            @empty
                <div class="text-secondary">No audit activity found.</div>
            @endforelse
        </div>
    </div>
@endsection
