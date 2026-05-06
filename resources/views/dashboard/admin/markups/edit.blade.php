@extends('layouts.dashboard')

@section('title', 'Edit Markup Rule')

@section('page-header')
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">Pricing Control</div>
            <h1 class="page-title">Edit markup rule</h1>
        </div>
    </div>
@endsection

@section('content')
    @include('dashboard.admin.markups.form')
@endsection
