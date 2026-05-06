@extends('layouts.dashboard')

@section('title', 'Edit Message Template')

@section('page-header')
    <h1 class="page-title">Edit Template</h1>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="post" action="{{ route('admin.settings.communications.templates.update', ['event' => $event, 'channel' => $channel]) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-2">
                            <label class="form-label">Subject</label>
                            <input class="form-control" name="subject" value="{{ old('subject', $template->subject) }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Body</label>
                            <textarea class="form-control" name="body" rows="8">{{ old('body', $template->body) }}</textarea>
                        </div>
                        <label class="form-check mb-3">
                            <input class="form-check-input" name="is_enabled" type="checkbox" value="1" {{ $template->is_enabled ? 'checked' : '' }}>
                            <span class="form-check-label">Template enabled</span>
                        </label>
                        <button type="submit" class="btn btn-primary">Save Template</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Allowed Variables</h3></div>
                <div class="card-body">
                    @foreach($allowedVariables as $var)
                        <div><code>{{ '{{ '.$var.' }}' }}</code></div>
                    @endforeach
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Preview sample</h3></div>
                <div class="card-body">
                    <div class="text-secondary">Use placeholders in subject/body. Rendering is plain safe replacement only.</div>
                </div>
            </div>
        </div>
    </div>
@endsection
