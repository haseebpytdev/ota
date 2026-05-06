@extends('layouts.frontend')

@section('title', 'Lookup booking')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="mb-3">Lookup your booking</h3>
                            <p class="text-secondary">For security, provide your booking reference and either matching email or phone. Access links are temporary.</p>
                            @if ($errors->any())
                                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                            @endif
                            <form method="post" action="{{ route('lookup-booking.submit') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Booking reference</label>
                                    <input class="form-control" name="booking_reference" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input class="form-control" name="email" type="email">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input class="form-control" name="phone">
                                </div>
                                <button class="btn btn-primary" type="submit">Lookup booking</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
