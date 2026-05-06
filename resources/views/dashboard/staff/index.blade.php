@extends('layouts.dashboard')

@section('title', 'Staff')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Operations</div>
            <h1 class="page-title">Staff portal</h1>
            <div class="text-secondary mt-1">Handle queue-based booking operations and support tasks assigned to your staff role.</div>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('staff.bookings.index') }}" class="btn btn-primary">View bookings queue</a>
            <span class="text-secondary small mb-0">Operate bookings allowed by your role (status &amp; notes).</span>
        </div>
    </div>
@endsection
