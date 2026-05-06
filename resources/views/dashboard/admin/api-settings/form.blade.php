@php
    $providerCredentialConfig = $providerCredentialConfig ?? config('supplier_credentials.providers', []);
    $selectedProvider = old('provider', $connection->provider?->value ?? 'mock');
    $selectedProviderFields = (array) data_get($providerCredentialConfig, $selectedProvider.'.fields', []);
    $isEdit = $connection->exists;
@endphp
<form method="POST" action="{{ $action }}" class="card">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Provider</label>
                <select name="provider" class="form-select" required data-provider-select>
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
        <p class="text-secondary small mb-2 d-none" data-duffel-help>
            Duffel uses a single access token. Use a test-mode token beginning with <code>duffel_test_</code> for sandbox testing.
        </p>
        @if (! empty($maskedCredentials))
            <div class="alert alert-secondary py-2">
                <div class="small text-secondary mb-1">Saved credentials are masked:</div>
                @foreach ($maskedCredentials as $key => $masked)
                    @if($key === 'access_token' && ($connection->provider?->value === 'duffel' || $selectedProvider === 'duffel'))
                        <div><code>{{ $key }}</code>: Stored token: {{ $masked }}</div>
                    @else
                        <div><code>{{ $key }}</code>: {{ $masked }}</div>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="row g-2" data-credentials-container>
            @foreach ($selectedProviderFields as $credentialKey => $fieldMeta)
                @php
                    $isSensitive = in_array($credentialKey, ['access_token', 'client_secret', 'password', 'token', 'api_key'], true);
                @endphp
                <div class="col-md-4">
                    <label class="form-label">{{ $fieldMeta['label'] ?? str_replace('_', ' ', ucfirst($credentialKey)) }}</label>
                    <input
                        type="{{ $fieldMeta['type'] ?? 'text' }}"
                        name="credentials[{{ $credentialKey }}]"
                        class="form-control"
                        value="{{ old('credentials.'.$credentialKey) }}"
                        placeholder="{{ $fieldMeta['placeholder'] ?? (($connection->exists && $isSensitive) ? 'Leave blank to keep existing token.' : '') }}"
                        @if (!empty($fieldMeta['required']) && !($isEdit && $selectedProvider === 'duffel' && $credentialKey === 'access_token')) required @endif
                    >
                    @if (!empty($fieldMeta['help']))
                        <div class="form-hint">{{ $fieldMeta['help'] }}</div>
                    @elseif($selectedProvider === 'duffel' && $credentialKey === 'access_token')
                        <div class="form-hint">Leave blank to keep existing token.</div>
                    @elseif($selectedProvider === 'duffel' && $credentialKey === 'api_version')
                        <div class="form-hint">Optional. Defaults to <code>v2</code>.</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="{{ route('admin.api-settings') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save connection</button>
    </div>
</form>

@once
    @push('scripts')
        <script>
            (function () {
                var select = document.querySelector('[data-provider-select]');
                if (!select) return;
                var container = document.querySelector('[data-credentials-container]');
                var duffelHelp = document.querySelector('[data-duffel-help]');
                var providerConfig = @json($providerCredentialConfig);
                var isEdit = @json($isEdit);

                function isSensitiveKey(key) {
                    return ['access_token', 'client_secret', 'password', 'token', 'api_key'].indexOf(key) !== -1;
                }

                function applyProvider(provider) {
                    if (!container) return;
                    var fields = (providerConfig[provider] && providerConfig[provider].fields) ? providerConfig[provider].fields : {};
                    var html = '';
                    Object.keys(fields).forEach(function (key) {
                        var meta = fields[key] || {};
                        var label = meta.label || key.replace(/_/g, ' ').replace(/\b\w/g, function (ch) { return ch.toUpperCase(); });
                        var type = meta.type || 'text';
                        var placeholder = meta.placeholder || (isSensitiveKey(key) ? 'Leave blank to keep existing token.' : '');
                        var required = (meta.required && !(isEdit && provider === 'duffel' && key === 'access_token')) ? 'required' : '';
                        var help = meta.help || '';
                        if (!help && provider === 'duffel' && key === 'access_token') {
                            help = 'Leave blank to keep existing token.';
                        } else if (!help && provider === 'duffel' && key === 'api_version') {
                            help = 'Optional. Defaults to v2.';
                        }
                        html += '<div class="col-md-4">';
                        html += '<label class="form-label">' + label + '</label>';
                        html += '<input type="' + type + '" name="credentials[' + key + ']" class="form-control" placeholder="' + placeholder + '" ' + required + '>';
                        if (help) {
                            html += '<div class="form-hint">' + help + '</div>';
                        }
                        html += '</div>';
                    });
                    container.innerHTML = html;
                    if (duffelHelp) {
                        duffelHelp.classList.toggle('d-none', provider !== 'duffel');
                    }
                }

                if (duffelHelp) {
                    duffelHelp.classList.toggle('d-none', select.value !== 'duffel');
                }
                select.addEventListener('change', function () {
                    applyProvider(select.value);
                });
            })();
        </script>
    @endpush
@endonce
