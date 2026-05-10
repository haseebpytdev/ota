@extends('layouts.dashboard')

@section('title', 'Edit user')

@section('page-header')
    <h1 class="page-title">Edit user</h1>
@endsection

@section('content')
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.users.update', $userModel) }}">
                @csrf
                @method('PATCH')
                @include('dashboard.admin.users.form')
                <div class="mt-3"><button class="btn btn-primary" type="submit">Update user</button></div>
            </form>
        </div>
    </div>
@endsection
