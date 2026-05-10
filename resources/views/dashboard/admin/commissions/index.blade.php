@extends('layouts.dashboard')

@section('title', 'Agent commissions')

@section('page-header')
    <h1 class="page-title">Agent commissions</h1>
@endsection

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-secondary">Pending commission</div><div class="h3 mb-0">Rs {{ number_format($kpis['pending'], 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-secondary">Approved unpaid</div><div class="h3 mb-0">Rs {{ number_format($kpis['approved_unpaid'], 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-secondary">Paid this month</div><div class="h3 mb-0">Rs {{ number_format($kpis['paid_this_month'], 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-secondary">Active agents</div><div class="h3 mb-0">{{ $kpis['active_agents'] }}</div></div></div></div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                <tr>
                    <th>Agent code</th>
                    <th>Agent</th>
                    <th>Balance</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($agents as $agent)
                    <tr>
                        <td>{{ $agent->code }}</td>
                        <td>{{ $agent->user?->name ?? 'N/A' }}</td>
                        <td>Rs {{ number_format((float) ($balances[$agent->id] ?? 0), 2) }}</td>
                        <td><a href="{{ route('admin.commissions.show', $agent) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-secondary">No agents found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
