@php
    $providerCredentialConfig = $providerCredentialConfig ?? config('supplier_credentials.providers', []);
    $selectedProvider = old('provider', $connection->provider?->value ?? 'duffel');
    $selectedProviderFields = (array) data_get($providerCredentialConfig, $selectedProvider.'.fields', []);
    $isEdit = $connection->exists;
    $providerLabel = ucfirst(str_replace('_', ' ', $connection->provider?->value ?? 'Provider'));
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
    <div class="card-footer d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <a href="{{ route('admin.api-settings') }}" class="btn btn-outline-secondary">Cancel</a>
        <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
            @if ($isEdit && isset($deleteAction))
                <button
                    type="button"
                    class="btn btn-outline-danger"
                    data-open-delete-confirm
                    aria-haspopup="dialog"
                    aria-controls="ota-delete-connection-modal"
                >
                    Delete connection
                </button>
            @endif
            <button type="submit" class="btn btn-primary">Save connection</button>
        </div>
    </div>
</form>

@if ($isEdit && isset($deleteAction))
    <form method="POST" action="{{ $deleteAction }}" class="d-none" id="ota-delete-connection-form">
        @csrf
        @method('DELETE')
    </form>
    <div
        id="ota-delete-connection-modal"
        class="ota-confirm-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ota-delete-connection-modal-title"
        hidden
    >
        <div class="ota-confirm-modal__backdrop" data-close-delete-confirm></div>
        <div class="ota-confirm-modal__panel" role="document">
            <h4 id="ota-delete-connection-modal-title" class="ota-confirm-modal__title">Delete {{ $providerLabel }} Connection</h4>
            <p class="ota-confirm-modal__message">
                Warning! You are about to delete the {{ $providerLabel }} Connection. Do you confirm the change?
            </p>
            <div class="ota-confirm-modal__actions">
                <button type="submit" class="btn btn-danger" form="ota-delete-connection-form">Yes</button>
                <button type="button" class="btn btn-outline-secondary" data-close-delete-confirm>No</button>
            </div>
        </div>
    </div>
    <style>
        .ota-confirm-modal[hidden] {
            display: none !important;
        }
        .ota-confirm-modal {
            position: fixed;
            inset: 0;
            z-index: 2100;
            display: grid;
            place-items: center;
            padding: 1rem;
        }
        .ota-confirm-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
        }
        .ota-confirm-modal__panel {
            position: relative;
            width: min(100%, 460px);
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
            padding: 1rem 1rem 0.95rem;
        }
        .ota-confirm-modal__title {
            margin: 0 0 0.45rem;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }
        .ota-confirm-modal__message {
            margin: 0;
            font-size: 0.92rem;
            line-height: 1.45;
            color: #334155;
        }
        .ota-confirm-modal__actions {
            margin-top: 0.9rem;
            display: flex;
            gap: 0.55rem;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        @media (max-width: 575px) {
            .ota-confirm-modal__panel {
                padding: 0.9rem 0.85rem 0.85rem;
            }
            .ota-confirm-modal__actions {
                justify-content: stretch;
            }
            .ota-confirm-modal__actions .btn {
                flex: 1 1 auto;
            }
        }
    </style>
@endif

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
            (function () {
                var modal = document.getElementById('ota-delete-connection-modal');
                if (!modal) return;
                var openBtn = document.querySelector('[data-open-delete-confirm]');
                var closeButtons = modal.querySelectorAll('[data-close-delete-confirm]');
                var lastFocused = null;

                function openModal() {
                    lastFocused = document.activeElement;
                    modal.hidden = false;
                    document.body.classList.add('overflow-hidden');
                    if (confirmBtn) confirmBtn.focus();
                }

                function closeModal() {
                    modal.hidden = true;
                    document.body.classList.remove('overflow-hidden');
                    if (lastFocused && typeof lastFocused.focus === 'function') {
                        lastFocused.focus();
                    }
                }

                if (openBtn) {
                    openBtn.addEventListener('click', openModal);
                }
                closeButtons.forEach(function (btn) {
                    btn.addEventListener('click', closeModal);
                });
                document.addEventListener('keydown', function (event) {
                    if (modal.hidden) return;
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        closeModal();
                    }
                });
            })();
        </script>
    @endpush
@endonce
