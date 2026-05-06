@extends('layouts.dashboard')

@section('title', 'Users & Access')

@section('page-header')
    <h1 class="page-title">Users & Access</h1>
@endsection

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-2"><div class="card"><div class="card-body"><small>Total</small><div class="h4 mb-0">{{ $kpis['total'] }}</div></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><small>Staff</small><div class="h4 mb-0">{{ $kpis['staff'] }}</div></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><small>Agents</small><div class="h4 mb-0">{{ $kpis['agents'] }}</div></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body"><small>Customers</small><div class="h4 mb-0">{{ $kpis['customers'] }}</div></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><small>Suspended / Invited</small><div class="h4 mb-0">{{ $kpis['suspended_or_invited'] }}</div></div></div></div>
    </div>
    <form class="card card-body mb-3" method="get" action="{{ route('admin.users.index') }}">
        <div class="row g-2">
            <div class="col-md-3"><input class="form-control" name="search" placeholder="Search name/email" value="{{ $filters['search'] ?? '' }}"></div>
            <div class="col-md-3">
                <select class="form-select" name="account_type">
                    <option value="">All account types</option>
                    @foreach(['agency_admin','staff','agent','customer','platform_admin'] as $t)
                        <option value="{{ $t }}" @selected(($filters['account_type'] ?? '') === $t)>{{ str_replace('_',' ',$t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    @foreach(['active','invited','suspended','inactive'] as $s)
                        <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary w-100" type="submit">Filter</button>
                <a href="{{ route('admin.users.create') }}" class="btn btn-outline-primary w-100">Create user</a>
            </div>
        </div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead><tr><th>Name</th><th>Email</th><th>Type</th><th>Status</th><th>Agency</th><th>Last login</th><th></th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->account_type?->value }}</td>
                        <td>{{ $user->status?->value }}</td>
                        <td>{{ $user->currentAgency?->name ?? 'N/A' }}</td>
                        <td>{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                        <td><a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-secondary">No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $users->links() }}</div>
@endsection
