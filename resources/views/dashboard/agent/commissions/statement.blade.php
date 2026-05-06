@extends('layouts.dashboard')

@section('title', 'Commission statement')

@section('page-header')
    <h1 class="page-title">Statement {{ $statement->statement_number ?? '#'.$statement->id }}</h1>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <div><strong>Period:</strong> {{ $statement->period_start?->format('Y-m-d') ?? 'N/A' }} - {{ $statement->period_end?->format('Y-m-d') ?? 'N/A' }}</div>
            <div><strong>Status:</strong> {{ $statement->status->value }}</div>
            <div><strong>Closing balance:</strong> Rs {{ number_format((float) $statement->closing_balance, 2) }}</div>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead><tr><th>Date</th><th>Type</th><th>Booking</th><th>Amount</th></tr></thead>
                <tbody>
                @foreach($statement->entries as $entry)
                    <tr>
                        <td>{{ $entry->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $entry->type->value }}</td>
                        <td>{{ $entry->booking?->booking_reference ?? 'N/A' }}</td>
                        <td>Rs {{ number_format((float) $entry->commission_amount, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
