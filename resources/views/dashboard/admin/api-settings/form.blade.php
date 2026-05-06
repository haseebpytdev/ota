<form method="POST" action="{{ $action }}" class="card">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Provider</label>
                <select name="provider" class="form-select" required>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->value }}" @selected(old('provider', $connection->provider?->value) === $provider->value)>
                            {{ str_replace('_', ' ', $provider->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $connection->name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Base URL</label>
                <input type="url" name="base_url" class="form-control" value="{{ old('base_url', $connection->base_url) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Environment</label>
                <select name="environment" class="form-select" required>
                    @foreach ($environments as $environment)
                        <option value="{{ $environment->value }}" @selected(old('environment', $connection->environment?->value ?? 'demo') === $environment->value)>
                            {{ ucfirst($environment->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $connection->status?->value ?? 'inactive') === $status->value)>
                            {{ ucfirst($status->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Settings (JSON)</label>
                <textarea name="settings_json" class="form-control" rows="2">{{ old('settings_json', $connection->settings ? json_encode($connection->settings, JSON_PRETTY_PRINT) : '{}') }}</textarea>
            </div>
        </div>

        <hr class="my-3">
        <h4 class="mb-2">Credentials</h4>
        @if (! empty($maskedCredentials))
            <div class="alert alert-secondary py-2">
                <div class="small text-secondary mb-1">Saved credentials are masked:</div>
                @foreach ($maskedCredentials as $key => $masked)
                    <div><code>{{ $key }}</code>: {{ $masked }}</div>
                @endforeach
            </div>
        @endif

        <div class="row g-2">
            @foreach (['access_token', 'api_version', 'client_id', 'client_secret', 'api_key', 'username', 'password', 'token'] as $credentialKey)
                <div class="col-md-4">
                    <label class="form-label text-capitalize">{{ str_replace('_', ' ', $credentialKey) }}</label>
                    <input type="text" name="credentials[{{ $credentialKey }}]" class="form-control" value="{{ old('credentials.'.$credentialKey) }}">
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="{{ route('admin.api-settings') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save connection</button>
    </div>
</form>
