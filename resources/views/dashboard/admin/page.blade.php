@extends('layouts.dashboard')

@section('title', $title)

@section('content')
    <div class="card">
        <div class="card-body">
            <h2 class="card-title">{{ $title }}</h2>
            <p class="text-secondary">{{ $description }}</p>
            {{-- Dynamic: datatables, forms, charts per section --}}
        </div>
    </div>
@endsection
