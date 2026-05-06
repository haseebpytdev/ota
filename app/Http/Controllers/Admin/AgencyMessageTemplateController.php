<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationTemplateEvent;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyMessageTemplate;
use App\Services\Communication\AgencyCommunicationSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AgencyMessageTemplateController extends Controller
{
    public function __construct(
        protected AgencyCommunicationSettingsService $settingsService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AgencyMessageTemplate::class);
        $agency = Agency::query()->findOrFail($request->user()->current_agency_id);
        $templates = AgencyMessageTemplate::query()->where('agency_id', $agency->id)->orderBy('event')->orderBy('channel')->get();

        return view('dashboard.admin.settings.communications.templates', [
            'agency' => $agency,
            'templates' => $templates,
            'events' => CommunicationTemplateEvent::cases(),
            'channels' => CommunicationChannel::cases(),
        ]);
    }

    public function edit(Request $request, string $event, string $channel): View
    {
        Gate::authorize('viewAny', AgencyMessageTemplate::class);
        $agency = Agency::query()->findOrFail($request->user()->current_agency_id);
        $template = AgencyMessageTemplate::query()->firstOrNew([
            'agency_id' => $agency->id,
            'event' => $event,
            'channel' => $channel,
        ], ['body' => '', 'is_enabled' => true]);

        return view('dashboard.admin.settings.communications.template-edit', [
            'template' => $template,
            'event' => $event,
            'channel' => $channel,
            'allowedVariables' => ['agency_name', 'booking_reference', 'passenger_name'],
        ]);
    }

    public function update(Request $request, string $event, string $channel): RedirectResponse
    {
        $agency = Agency::query()->findOrFail($request->user()->current_agency_id);
        $existing = AgencyMessageTemplate::query()->firstOrNew([
            'agency_id' => $agency->id,
            'event' => $event,
            'channel' => $channel,
        ]);
        Gate::authorize('update', $existing);

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'is_enabled' => ['nullable', 'boolean'],
            'variables' => ['nullable', 'array'],
        ]);
        $validated['is_enabled'] = $request->boolean('is_enabled');

        $this->settingsService->updateTemplate($agency, $request->user(), $event, $channel, $validated);

        return redirect()->route('admin.settings.communications.templates.index')->with('status', 'communication-template-updated');
    }
}
