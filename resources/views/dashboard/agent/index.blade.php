@extends('layouts.dashboard')

@section('title', 'Agent')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Agency</div>
            <h1 class="page-title">Asif Travels agent portal</h1>
            <div class="text-secondary mt-1">Create and track booking requests for your customers and monitor commissions in one place.</div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-md-12">
            <div class="card h-100 ota-quick-action-card">
                <div class="card-body">
                    <div class="fw-bold">Commission balance</div>
                    <div class="text-secondary small mt-1">Current approved/paid ledger balance: Rs {{ number_format((float) ($commissionBalance ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <a href="{{ route('agent.bookings.index') }}" class="text-reset text-decoration-none">
                <div class="card h-100 ota-quick-action-card">
                    <div class="card-body">
                        <div class="ota-quick-icon"><i class="ti ti-ticket"></i></div>
                        <div class="fw-bold">My bookings</div>
                        <div class="text-secondary small mt-1">View all booking requests submitted by your agent account.</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('agent.bookings.create') }}" class="text-reset text-decoration-none">
                <div class="card h-100 ota-quick-action-card">
                    <div class="card-body">
                        <div class="ota-quick-icon"><i class="ti ti-plus"></i></div>
                        <div class="fw-bold">Create booking request</div>
                        <div class="text-secondary small mt-1">Submit a new customer booking request from available supplier fares.</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('agent.commissions.index') }}" class="text-reset text-decoration-none">
                <div class="card h-100 ota-quick-action-card">
                    <div class="card-body">
                        <div class="ota-quick-icon"><i class="ti ti-report-money"></i></div>
                        <div class="fw-bold">My commissions</div>
                        <div class="text-secondary small mt-1">Track pending, approved, paid commissions and statements.</div>
                    </div>
                </div>
            </a>
        </div>
    </div>
@endsection
