<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\AgentPortal;

use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Lead;
use App\Services\CRM\Agents\AgentReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AG-003 — Agent portal: submit leads and track their status
final class AgentPortalLeadController extends Controller
{
    public function __construct(private readonly AgentReferralService $referralService) {}

    public function index(Request $request): View
    {
        /** @var Agent $agent */
        $agent = $request->attributes->get('agent');

        $leads = $agent->leads()
            ->with(['assignedCounsellor', 'programmeInterests'])
            ->latest()
            ->paginate(20);

        return view('agent-portal.leads.index', compact('agent', 'leads'));
    }

    public function create(Request $request): View
    {
        /** @var Agent $agent */
        $agent = $request->attributes->get('agent');

        $programmes = CrmProgramme::withoutGlobalScopes()
            ->where('institution_id', $agent->institution_id)
            ->pluck('name', 'id');

        return view('agent-portal.leads.create', compact('agent', 'programmes'));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Agent $agent */
        $agent = $request->attributes->get('agent');

        $validated = $request->validate([
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'max:255'],
            'mobile'        => ['required', 'string', 'max:20'],
            'programme_id'  => ['nullable', 'integer', 'exists:crm_programmes,id'],
            'notes'         => ['nullable', 'string', 'max:1000'],
            'consent_given' => ['accepted'],
        ]);

        $lead = Lead::withoutGlobalScopes()->create([
            'institution_id'    => $agent->institution_id,
            'first_name'        => $validated['first_name'],
            'last_name'         => $validated['last_name'],
            'email'             => $validated['email'],
            'mobile'            => $validated['mobile'],
            'source'            => LeadSource::AGENT,
            'status'            => LeadStatus::NEW_ENQUIRY,
            'agent_id'          => $agent->id,
            'notes'             => $validated['notes'] ?? null,
            'consent_given'     => true,
            'consent_timestamp' => now(),
            'consent_ip'        => $request->ip(),
        ]);

        // Attach programme interest if provided
        if (! empty($validated['programme_id'])) {
            $lead->programmeInterests()->attach($validated['programme_id'], [
                'is_primary'      => true,
                'institution_id'  => $agent->institution_id,
            ]);
        }

        $this->referralService->incrementLeadCount($agent);

        return redirect()->route('agent-portal.leads.index')
            ->with('success', 'Lead submitted successfully.');
    }
}
