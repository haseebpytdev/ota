<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Services\Agencies\AgencyBrandingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AgencyBrandingController extends Controller
{
    public function __construct(
        protected AgencyBrandingService $brandingService,
    ) {}

    public function edit(Request $request): View
    {
        $agency = $this->resolveAgency($request);
        Gate::authorize('view', $agency);
        $settings = $this->brandingService->getSettingsForAgency($agency);

        return view('dashboard.admin.settings.branding', [
            'agency' => $agency,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $agency = $this->resolveAgency($request);
        Gate::authorize('update', $agency);

        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:100'],
            'support_whatsapp' => ['nullable', 'string', 'max:100'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'office_address' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'max:10'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'header_cta_label' => ['nullable', 'string', 'max:255'],
            'header_cta_url' => ['nullable', 'url', 'max:255'],
            'footer_about' => ['nullable', 'string', 'max:3000'],
            'footer_copyright' => ['nullable', 'string', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'social_links.facebook' => ['nullable', 'url', 'max:255'],
            'social_links.instagram' => ['nullable', 'url', 'max:255'],
            'social_links.linkedin' => ['nullable', 'url', 'max:255'],
            'social_links.twitter' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'favicon' => ['nullable', 'file', 'max:1024'],
            'hero_image' => ['nullable', 'image', 'max:5120'],
            'footer_logo' => ['nullable', 'image', 'max:5120'],
        ]);

        $setting = $this->brandingService->getSettingsForAgency($agency);
        foreach ([
            'logo' => 'logo_path',
            'favicon' => 'favicon_path',
            'hero_image' => 'hero_image_path',
            'footer_logo' => 'footer_logo_path',
        ] as $inputKey => $settingKey) {
            if ($request->hasFile($inputKey)) {
                $media = $this->brandingService->uploadMedia($agency, $request->user(), $request->file($inputKey), 'branding', null);
                $validated[$settingKey] = $media->file_path;
            }
        }

        $this->brandingService->updateSettings($agency, $request->user(), $validated);

        return back()->with('status', 'branding-updated');
    }

    protected function resolveAgency(Request $request): Agency
    {
        $user = $request->user();
        if ($user->isPlatformAdmin() && $request->filled('agency_id')) {
            return Agency::query()->findOrFail($request->integer('agency_id'));
        }

        return Agency::query()->findOrFail($user->current_agency_id);
    }
}
