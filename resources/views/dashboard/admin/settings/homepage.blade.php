@extends('layouts.dashboard')

@section('title', 'Homepage Settings')

@section('page-header')
    <h1 class="page-title">Settings / Homepage</h1>
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @foreach($sections as $section)
        <form method="post" action="{{ route('admin.settings.homepage.update', $section->section_key) }}" enctype="multipart/form-data" class="card mb-3">
            @csrf
            @method('PATCH')
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0 text-capitalize">{{ str_replace('_', ' ', $section->section_key) }}</h3>
                <label class="form-check m-0"><input class="form-check-input" type="checkbox" name="is_enabled" value="1" @checked($section->is_enabled)><span class="form-check-label">Enabled</span></label>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label">Title</label><input class="form-control" name="title" value="{{ old('title', $section->title) }}"></div>
                    <div class="col-md-6"><label class="form-label">Image</label><input type="file" class="form-control" name="image"></div>
                    <div class="col-12"><label class="form-label">Subtitle</label><textarea class="form-control" rows="2" name="subtitle">{{ old('subtitle', $section->subtitle) }}</textarea></div>
                    <div class="col-12"><label class="form-label">Content (JSON structure)</label><textarea class="form-control" rows="4" name="content">@json(old('content', $section->content ?? []), JSON_PRETTY_PRINT)</textarea></div>
                    <div class="col-md-3"><label class="form-label">Sort order</label><input class="form-control" type="number" name="sort_order" value="{{ old('sort_order', $section->sort_order) }}"></div>
                </div>
                <div class="mt-2 d-flex justify-content-end"><button class="btn btn-outline-primary">Save section</button></div>
            </div>
        </form>
    @endforeach
@endsection
