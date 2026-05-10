<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyMedia;
use App\Services\Agencies\AgencyBrandingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AgencyMediaController extends Controller
{
    public function __construct(
        protected AgencyBrandingService $brandingService,
    ) {}

    public function index(Request $request): View
    {
        $agency = Agency::query()->findOrFail($request->user()->current_agency_id);
        Gate::authorize('viewAny', [AgencyMedia::class, $agency]);

        return view('dashboard.admin.settings.media', [
            'agency' => $agency,
            'mediaItems' => $agency->media()->latest('id')->paginate(24),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $agency = Agency::query()->findOrFail($request->user()->current_agency_id);
        Gate::authorize('create', [AgencyMedia::class, $agency]);
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:5120'],
            'collection' => ['nullable', 'string', 'max:50'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $this->brandingService->uploadMedia(
            $agency,
            $request->user(),
            $request->file('file'),
            $validated['collection'] ?? 'general',
            $validated['alt_text'] ?? null,
        );

        return back()->with('status', 'media-uploaded');
    }

    public function destroy(Request $request, AgencyMedia $agencyMedia): RedirectResponse
    {
        Gate::authorize('delete', $agencyMedia);
        $this->brandingService->deleteMedia($agencyMedia, $request->user());

        return back()->with('status', 'media-deleted');
    }
}
