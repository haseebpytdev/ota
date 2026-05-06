@extends('layouts.dashboard')

@section('title', 'User details')

@section('page-header')
    <h1 class="page-title">User: {{ $userModel->name }}</h1>
@endsection

@section('content')
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1"><strong>Email:</strong> {{ $userModel->email }}</p>
            <p class="mb-1"><strong>Type:</strong> {{ $userModel->account_type?->value }}</p>
            <p class="mb-1"><strong>Status:</strong> {{ $userModel->status?->value }}</p>
            <p class="mb-0"><strong>Agency:</strong> {{ $userModel->currentAgency?->name ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <a class="btn btn-outline-primary w-100" href="{{ route('admin.users.edit', $userModel) }}">Edit user</a>
        </div>
        <div class="col-md-4">
            <form method="post" action="{{ route('admin.users.send-invite', $userModel) }}">@csrf<button class="btn btn-outline-primary w-100" type="submit">Send invite</button></form>
        </div>
        <div class="col-md-4">
            <form method="post" action="{{ route('admin.users.reset-password-link', $userModel) }}">@csrf<button class="btn btn-outline-primary w-100" type="submit">Send reset link</button></form>
        </div>
        <div class="col-md-6">
            <form method="post" action="{{ route('admin.users.suspend', $userModel) }}">@csrf @method('PATCH')<button class="btn btn-outline-danger w-100" type="submit">Suspend</button></form>
        </div>
        <div class="col-md-6">
            <form method="post" action="{{ route('admin.users.activate', $userModel) }}">@csrf @method('PATCH')<button class="btn btn-outline-success w-100" type="submit">Activate</button></form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Linked data</h3></div>
        <div class="card-body">
            @if($userModel->account_type?->value === 'agent')
                <p class="mb-1"><strong>Agent code:</strong> {{ $userModel->agentProfile?->code ?? 'N/A' }}</p>
                <p class="mb-1"><strong>Commission %:</strong> {{ $userModel->agentProfile?->commission_percent ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Total bookings:</strong> {{ $userModel->agentProfile?->bookings?->count() ?? 0 }}</p>
            @elseif($userModel->account_type?->value === 'staff')
                <p class="mb-1"><strong>Department:</strong> {{ $userModel->staffProfile?->department ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Role title:</strong> {{ $userModel->staffProfile?->job_title ?? 'N/A' }}</p>
            @elseif($userModel->account_type?->value === 'customer')
                <p class="mb-0"><strong>Recent bookings:</strong> {{ $userModel->bookings()->count() }}</p>
            @else
                <p class="text-secondary mb-0">No linked profile details.</p>
            @endif
        </div>
    </div>
@endsection
