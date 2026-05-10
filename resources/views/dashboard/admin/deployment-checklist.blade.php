@extends('layouts.dashboard')

@section('title', 'Deployment Checklist')

@section('page-header')
    <h1 class="page-title">Deployment Checklist</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Pre-deployment readiness</h3></div>
        <div class="card-body">
            @foreach($items as $item)
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span>{{ $item['label'] }}</span>
                    <span class="badge {{ $item['ok'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $item['ok'] ? 'Ready' : 'Review' }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endsection
