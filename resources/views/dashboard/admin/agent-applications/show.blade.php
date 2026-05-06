@extends('layouts.dashboard')

@section('title', 'Agent application review')

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Application details</h3></div>
                <div class="card-body">
                    <p><strong>Name:</strong> {{ $application->first_name }} {{ $application->last_name }}</p>
                    <p><strong>Email:</strong> {{ $application->email }}</p>
                    <p><strong>Mobile:</strong> {{ $application->mobile }}</p>
                    <p><strong>Company:</strong> {{ $application->company_name }}</p>
                    <p><strong>Business type:</strong> {{ $application->business_type }}</p>
                    <p><strong>Location:</strong> {{ $application->city }}, {{ $application->country }}</p>
                    <p><strong>Address:</strong> {{ $application->office_address }}</p>
                    <p><strong>Website:</strong> {{ $application->website ?? '—' }}</p>
                    <p><strong>CNIC:</strong> {{ $application->cnic ?? '—' }}</p>
                    <p><strong>NTN:</strong> {{ $application->ntn ?? '—' }}</p>
                    <p><strong>IATA:</strong> {{ $application->iata_number ?? '—' }}</p>
                    <p><strong>Years in business:</strong> {{ $application->years_in_business ?? '—' }}</p>
                    <p><strong>Expected volume:</strong> {{ $application->expected_booking_volume ?? '—' }}</p>
                    <p><strong>Services:</strong> {{ is_array($application->services_interested) ? implode(', ', $application->services_interested) : '—' }}</p>
                    <p><strong>Applicant notes:</strong> {{ $application->notes ?? '—' }}</p>
                    <p><strong>Status:</strong> <span class="badge bg-secondary">{{ $application->status }}</span></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Review actions</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.agent-applications.approve', $application) }}" class="mb-2">
                        @csrf @method('PATCH')
                        <textarea class="form-control mb-2" name="internal_note" rows="2" placeholder="Internal note (optional)"></textarea>
                        <button class="btn btn-success w-100" type="submit">Approve and create agent account</button>
                    </form>
                    <form method="POST" action="{{ route('admin.agent-applications.needs-more-info', $application) }}" class="mb-2">
                        @csrf @method('PATCH')
                        <textarea class="form-control mb-2" name="internal_note" rows="2" placeholder="Information required"></textarea>
                        <button class="btn btn-warning w-100" type="submit">Mark needs more info</button>
                    </form>
                    <form method="POST" action="{{ route('admin.agent-applications.reject', $application) }}">
                        @csrf @method('PATCH')
                        <textarea class="form-control mb-2" name="internal_note" rows="2" placeholder="Reason (optional)"></textarea>
                        <button class="btn btn-danger w-100" type="submit">Reject application</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
