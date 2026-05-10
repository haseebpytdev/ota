@extends('layouts.dashboard')

@section('title', 'Customer dashboard')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Customer Portal</div>
            <h1 class="page-title">My bookings</h1>
        </div>
    </div>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="text-secondary small">Total</div><div class="h2 mb-0">{{ $kpis['total'] }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="text-secondary small">Pending</div><div class="h2 mb-0">{{ $kpis['pending'] }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="text-secondary small">Payment pending</div><div class="h2 mb-0">{{ $kpis['payment_pending'] }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="text-secondary small">Ticketed</div><div class="h2 mb-0">{{ $kpis['ticketed'] }}</div></div></div></div>
    </div>
    <a href="{{ route('customer.bookings.index') }}" class="btn btn-primary">View my bookings</a>
@endsection
