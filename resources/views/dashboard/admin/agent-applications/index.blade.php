@extends('layouts.dashboard')

@section('title', 'Agent applications')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Partner onboarding</div>
            <h1 class="page-title">Agent applications</h1>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $application)
                        <tr>
                            <td>{{ $application->first_name }} {{ $application->last_name }}</td>
                            <td>{{ $application->company_name }}</td>
                            <td>{{ $application->email }}</td>
                            <td><span class="badge bg-secondary">{{ $application->status }}</span></td>
                            <td>{{ $application->created_at?->format('Y-m-d H:i') }}</td>
                            <td><a class="btn btn-sm btn-primary" href="{{ route('admin.agent-applications.show', $application) }}">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No agent applications yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $applications->links() }}</div>
@endsection
