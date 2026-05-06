@extends('layouts.dashboard')

@section('title', 'My commissions')

@section('page-header')
    <h1 class="page-title">My commissions</h1>
@endsection

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-secondary">Current balance</div><div class="h4 mb-0">Rs {{ number_format($balance, 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-secondary">Pending</div><div class="h4 mb-0">Rs {{ number_format($pending, 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-secondary">Approved</div><div class="h4 mb-0">Rs {{ number_format($approved, 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-secondary">Paid</div><div class="h4 mb-0">Rs {{ number_format($paid, 2) }}</div></div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Entries</h3></div>
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead><tr><th>Date</th><th>Type</th><th>Status</th><th>Booking</th><th>Amount</th></tr></thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-capitalize">{{ $entry->type->value }}</td>
                        <td class="text-capitalize">{{ $entry->status->value }}</td>
                        <td>{{ $entry->booking?->booking_reference ?? 'N/A' }}</td>
                        <td>Rs {{ number_format((float) $entry->commission_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary">No commission entries yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Statements</h3></div>
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead><tr><th>Statement</th><th>Period</th><th>Status</th><th>Closing balance</th><th></th></tr></thead>
                <tbody>
                @forelse($statements as $statement)
                    <tr>
                        <td>{{ $statement->statement_number ?? 'N/A' }}</td>
                        <td>{{ $statement->period_start?->format('Y-m-d') ?? 'N/A' }} - {{ $statement->period_end?->format('Y-m-d') ?? 'N/A' }}</td>
                        <td class="text-capitalize">{{ $statement->status->value }}</td>
                        <td>Rs {{ number_format((float) $statement->closing_balance, 2) }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('agent.commissions.statements.show', $statement) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary">No statements yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
