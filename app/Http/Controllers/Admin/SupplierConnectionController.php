<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupplierConnectionStatus;
use App\Enums\SupplierEnvironment;
use App\Enums\SupplierProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupplierConnectionRequest;
use App\Http\Requests\Admin\UpdateSupplierConnectionRequest;
use App\Models\SupplierConnection;
use App\Services\Suppliers\SupplierConnectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplierConnectionController extends Controller
{
    public function __construct(
        protected SupplierConnectionService $service,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SupplierConnection::class);

        $query = $this->scopedQuery($request->user());
        $connections = (clone $query)->orderBy('provider')->paginate(20);

        $kpiBase = $this->scopedQuery($request->user());
        $kpis = [
            'total' => (clone $kpiBase)->count(),
            'active' => (clone $kpiBase)->where('status', SupplierConnectionStatus::Active)->count(),
            'sandbox' => (clone $kpiBase)->where('environment', SupplierEnvironment::Sandbox)->count(),
            'live' => (clone $kpiBase)->where('environment', SupplierEnvironment::Live)->count(),
        ];
        $activeRealSupplierExists = (clone $kpiBase)
            ->where('status', SupplierConnectionStatus::Active)
            ->exists();

        return view('dashboard.admin.api-settings.index', [
            'connections' => $connections,
            'kpis' => $kpis,
            'hasRows' => $connections->count() > 0,
            'fallbackSuppliers' => config('ota-suppliers.suppliers', []),
            'activeRealSupplierExists' => $activeRealSupplierExists,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', SupplierConnection::class);

        return view('dashboard.admin.api-settings.create', [
            'connection' => new SupplierConnection,
            'providers' => SupplierProvider::cases(),
            'environments' => SupplierEnvironment::cases(),
            'statuses' => SupplierConnectionStatus::cases(),
            'maskedCredentials' => [],
            'providerCredentialConfig' => config('supplier_credentials.providers', []),
            'action' => route('admin.api-settings.store'),
            'method' => 'POST',
        ]);
    }

    public function store(StoreSupplierConnectionRequest $request): RedirectResponse
    {
        Gate::authorize('create', SupplierConnection::class);
        $agency = $request->user()->currentAgency;
        abort_if($agency === null, 403, 'No agency context assigned.');

        $this->service->storeConnection($agency, $this->payload($request));

        return redirect()->route('admin.api-settings')->with('status', 'supplier-connection-created');
    }

    public function edit(SupplierConnection $supplierConnection): View
    {
        Gate::authorize('view', $supplierConnection);

        return view('dashboard.admin.api-settings.edit', [
            'connection' => $supplierConnection,
            'providers' => SupplierProvider::cases(),
            'environments' => SupplierEnvironment::cases(),
            'statuses' => SupplierConnectionStatus::cases(),
            'maskedCredentials' => $supplierConnection->maskedCredentials(),
            'providerCredentialConfig' => config('supplier_credentials.providers', []),
            'action' => route('admin.api-settings.update', $supplierConnection),
            'method' => 'PATCH',
        ]);
    }

    public function update(UpdateSupplierConnectionRequest $request, SupplierConnection $supplierConnection): RedirectResponse
    {
        Gate::authorize('update', $supplierConnection);
        $this->service->updateConnection($supplierConnection, $this->payload($request, $supplierConnection));

        return redirect()->route('admin.api-settings')->with('status', 'supplier-connection-updated');
    }

    public function destroy(SupplierConnection $supplierConnection): RedirectResponse
    {
        Gate::authorize('delete', $supplierConnection);
        $supplierConnection->delete();

        return redirect()->route('admin.api-settings')->with('status', 'supplier-connection-deleted');
    }

    public function test(Request $request, SupplierConnection $supplierConnection): RedirectResponse
    {
        Gate::authorize('update', $supplierConnection);
        $result = $this->service->testConnection($supplierConnection, $request->user());

        return back()->with('status', 'supplier-test-ran')->with('test_result', $result);
    }

    public function toggleStatus(Request $request, SupplierConnection $supplierConnection): RedirectResponse
    {
        Gate::authorize('update', $supplierConnection);
        $newStatus = $supplierConnection->status === SupplierConnectionStatus::Active
            ? SupplierConnectionStatus::Inactive
            : SupplierConnectionStatus::Active;

        $this->service->updateConnection($supplierConnection, [
            'status' => $newStatus,
            'is_active' => $newStatus === SupplierConnectionStatus::Active,
        ]);

        return back()->with('status', 'supplier-status-toggled');
    }

    protected function scopedQuery($user): Builder
    {
        $query = SupplierConnection::query()
            ->with([
                'latestReadinessDiagnostic',
                'latestSearchDiagnostic',
                'latestOrderDiagnostic',
            ]);
        if (! $user->isPlatformAdmin()) {
            $query->where('agency_id', $user->current_agency_id);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(Request $request, ?SupplierConnection $existing = null): array
    {
        $provider = $request->string('provider')->toString();
        $credentials = $request->input('credentials', []);
        if (! is_array($credentials)) {
            $credentials = [];
        }
        $providerFields = (array) config('supplier_credentials.providers.'.$provider.'.fields', []);
        $allowedKeys = array_keys($providerFields);
        $normalizedCredentials = [];

        $providerChanged = $existing !== null && $existing->provider->value !== $provider;
        $baseCredentials = $providerChanged ? [] : (($existing?->credentials && is_array($existing->credentials)) ? $existing->credentials : []);

        foreach ($allowedKeys as $key) {
            $raw = $credentials[$key] ?? null;
            $value = trim((string) $raw);
            if ($value !== '') {
                $normalizedCredentials[$key] = $value;
            } elseif ($existing === null) {
                $default = $providerFields[$key]['default'] ?? null;
                if (is_string($default) && $default !== '') {
                    $normalizedCredentials[$key] = $default;
                }
            }
        }

        $credentials = array_merge($baseCredentials, $normalizedCredentials);

        $settings = [];
        $settingsRaw = trim((string) $request->input('settings_json', ''));
        if ($settingsRaw !== '') {
            $decoded = json_decode($settingsRaw, true);
            if (is_array($decoded)) {
                $settings = $decoded;
            }
        }

        $meta = $request->input('meta', []);
        if (! is_array($meta)) {
            $meta = [];
        }

        $status = $request->input('status', SupplierConnectionStatus::Inactive->value);

        return [
            'provider' => $provider,
            'name' => $request->string('name')->toString(),
            'display_name' => $request->string('name')->toString(),
            'environment' => $request->string('environment')->toString(),
            'status' => $status,
            'base_url' => $request->string('base_url')->toString() ?: null,
            'credentials' => $credentials,
            'settings' => $settings,
            'meta' => $meta,
            'is_active' => $status === SupplierConnectionStatus::Active->value,
        ];
    }
}
