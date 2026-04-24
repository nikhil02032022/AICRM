<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Compliance;

use App\Http\Controllers\Controller;
use App\Models\CRM\Compliance\SecurityIncident;
use App\Services\CRM\Compliance\BreachNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-CR-010 — Breach notification workflow (72h)
final class SecurityIncidentController extends Controller
{
    public function __construct(private readonly BreachNotificationService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.compliance.incidents.view');

        $incidents = SecurityIncident::with('reportedBy:id,name')
            ->orderByDesc('detected_at')
            ->get();

        return view('crm.compliance.security-incidents.index', compact('incidents'));
    }

    public function create(): View
    {
        $this->authorize('crm.compliance.incidents.create');

        return view('crm.compliance.security-incidents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('crm.compliance.incidents.create');

        $validated = $request->validate([
            'incident_type' => 'required|string|max:100',
            'description'   => 'required|string',
            'detected_at'   => 'required|date',
        ]);

        $validated['institution_id'] = $request->user()->institution_id;

        $incident = $this->service->create($validated, $request->user());

        return redirect()->route('crm.compliance.security-incidents.show', $incident)
            ->with('success', 'Security incident reported.');
    }

    public function show(SecurityIncident $securityIncident): View
    {
        $this->authorize('crm.compliance.incidents.view');

        return view('crm.compliance.security-incidents.show', ['incident' => $securityIncident]);
    }

    public function update(Request $request, SecurityIncident $securityIncident): RedirectResponse
    {
        $this->authorize('crm.compliance.incidents.update');

        $validated = $request->validate([
            'status'             => 'required|in:reported,investigating,notified,resolved',
            'documentation_json' => 'nullable|array',
        ]);

        $securityIncident->update($validated);

        // Trigger notification if admin requests it
        if ($request->boolean('send_notification') && ! $securityIncident->notified_at) {
            $this->service->notify($securityIncident);
        }

        return redirect()->route('crm.compliance.security-incidents.show', $securityIncident)
            ->with('success', 'Incident updated.');
    }
}
