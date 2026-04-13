<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreTelecallingCampaignRequest;
use App\Models\CRM\Lead;
use App\Models\CRM\TelecallingCampaign;
use App\Models\User;
use App\Services\CRM\Communication\TelecallingCampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

// BRD: CRM-TC-006 — Web controller for telecalling campaign management
final class TelecallingCampaignWebController extends Controller
{
    public function __construct(
        private readonly TelecallingCampaignService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.campaigns.manage');

        $campaigns = $this->service->paginate($request->only(['status', 'search']), 12);

        $progressByUuid = [];
        foreach ($campaigns as $campaign) {
            $progressByUuid[$campaign->uuid] = [
                'total_leads' => (int) ($campaign->leads_count ?? 0),
                'total_agents' => (int) ($campaign->agents_count ?? 0),
                'sessions' => (int) $campaign->diallerSessions->count(),
                'placed_calls' => (int) $campaign->diallerSessions->sum('placed_calls'),
                'queued_calls' => (int) $campaign->diallerSessions->sum('queued_calls'),
                'skipped_calls' => (int) $campaign->diallerSessions->sum('skipped_calls'),
                'failed_calls' => (int) $campaign->diallerSessions->sum('failed_calls'),
            ];
        }

        $institutionId = (int) $request->user()->institution_id;
        $agents = $this->agentsForInstitution($institutionId);
        $candidateLeads = $this->candidateLeadsForInstitution($institutionId);

        return view('crm.communication.voice.campaigns', compact('campaigns', 'agents', 'candidateLeads', 'progressByUuid'));
    }

    public function edit(Request $request, TelecallingCampaign $telecallingCampaign): View
    {
        $this->authorize('crm.campaigns.manage');

        $institutionId = (int) $request->user()->institution_id;
        $campaign = $telecallingCampaign->load(['agents', 'leads.lead']);

        $selectedAgentIds = $campaign->agents
            ->pluck('user_id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        $selectedLeadUuids = $campaign->leads
            ->pluck('lead.uuid')
            ->filter(static fn ($uuid) => is_string($uuid) && $uuid !== '')
            ->values()
            ->all();

        $agents = $this->agentsForInstitution($institutionId);
        $candidateLeads = $this->candidateLeadsForInstitution($institutionId);

        return view('crm.communication.voice.campaigns-edit', compact('campaign', 'agents', 'candidateLeads', 'selectedAgentIds', 'selectedLeadUuids'));
    }

    public function store(StoreTelecallingCampaignRequest $request): RedirectResponse
    {
        $this->authorize('crm.campaigns.manage');

        try {
            $this->service->create(
                institutionId: (int) $request->user()->institution_id,
                createdBy: (int) $request->user()->id,
                payload: $request->validated(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['campaign' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'Telecalling campaign created successfully.');
    }

    public function update(StoreTelecallingCampaignRequest $request, TelecallingCampaign $telecallingCampaign): RedirectResponse
    {
        $this->authorize('crm.campaigns.manage');

        try {
            $this->service->update($telecallingCampaign, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['campaign' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'Telecalling campaign updated successfully.');
    }

    public function launch(TelecallingCampaign $telecallingCampaign): RedirectResponse
    {
        $this->authorize('crm.campaigns.manage');

        try {
            $this->service->launch($telecallingCampaign);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['campaign' => $exception->getMessage()]);
        }

        return back()->with('success', 'Telecalling campaign launched successfully.');
    }

    private function agentsForInstitution(int $institutionId)
    {
        return User::query()
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function candidateLeadsForInstitution(int $institutionId)
    {
        return Lead::query()
            ->where('institution_id', $institutionId)
            ->whereNull('dnc_at')
            ->where('opt_out', false)
            ->where('call_consent_given', true)
            ->whereNotNull('mobile')
            ->orderByDesc('lead_score')
            ->limit(100)
            ->get(['uuid', 'first_name', 'last_name', 'lead_score']);
    }
}
