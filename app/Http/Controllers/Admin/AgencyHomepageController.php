<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Services\Agencies\AgencyBrandingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AgencyHomepageController extends Controller
{
    public function __construct(
        protected AgencyBrandingService $brandingService,
    ) {}

    public function edit(Request $request): View
    {
        $agency = Agency::query()->findOrFail($request->user()->current_agency_id);
        Gate::authorize('view', $agency);
        $this->brandingService->getSettingsForAgency($agency);

        return view('dashboard.admin.settings.homepage', [
            'agency' => $agency,
            'sections' => $agency->homepageSections()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        $agency = Agency::query()->findOrFail($request->user()->current_agency_id);
        Gate::authorize('update', $agency);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:5000'],
            'content' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_enabled' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
        if (($validated['content'] ?? '') !== '') {
            $decoded = json_decode((string) $validated['content'], true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                return back()->withErrors(['content' => 'Content must be valid JSON object/array.'])->withInput();
            }
            $validated['content'] = $decoded;
        } else {
            $validated['content'] = null;
        }

        if ($request->hasFile('image')) {
            $media = $this->brandingService->uploadMedia($agency, $request->user(), $request->file('image'), 'homepage', null);
            $validated['image_path'] = $media->file_path;
        }

        $this->brandingService->updateHomepageSection($agency, $request->user(), $section, $validated);

        return back()->with('status', 'homepage-section-updated');
    }
}
