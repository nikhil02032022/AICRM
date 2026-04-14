<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreAgentCommissionRequest;
use App\Http\Requests\CRM\UpdateAgentCommissionRequest;
use App\Models\CRM\AgentCommission;
use App\Models\CRM\Lead;
use App\Services\CRM\Agent\AgentCommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AG-006 — Agent commission workflow web controller (session auth, Blade views)
final class AgentCommissionWebController extends Controller
{
    public function __construct(
        private readonly AgentCommissionService $service
    ) {}

    /**
     * BRD: CRM-AG-006 — List all commission records for the institution
     */
    public function index(Request $request): View
    {
        $user        = $request->user();
        $commissions = $this->service->list($user->institution_id);
        $leads       = Lead::query()
            ->where('institution_id', $user->institution_id)
            ->select(['uuid', 'first_name', 'last_name', 'mobile'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(200)
            ->get();

        return view('crm.agents.commission', compact('commissions', 'leads'));
    }

    /**
     * BRD: CRM-AG-006 — Create a commission record for an enrolment
     */
    public function store(StoreAgentCommissionRequest $request): RedirectResponse
    {
        $user      = $request->user();
        $validated = $request->validated();
        $lead      = Lead::where('uuid', $validated['lead_uuid'])
            ->where('institution_id', $user->institution_id)
            ->firstOrFail();

        $this->service->create(
            agentUserId:      (int) $validated['agent_user_id'],
            lead:             $lead,
            commissionType:   $validated['commission_type'],
            commissionAmount: (float) ($validated['commission_amount'] ?? 0),
            percentageRate:   isset($validated['percentage_rate']) ? (float) $validated['percentage_rate'] : null,
            baseAmount:       isset($validated['base_amount']) ? (float) $validated['base_amount'] : null,
        );

        return back()->with('success', 'Commission record created and queued for processing.');
    }

    /**
     * BRD: CRM-AG-006 — Approve, reject, or mark as paid
     */
    public function update(UpdateAgentCommissionRequest $request, AgentCommission $agentCommission): RedirectResponse
    {
        $validated = $request->validated();
        $user      = $request->user();

        match ($validated['action']) {
            'approve' => $this->service->approve($agentCommission, $user->id, $validated['approval_notes'] ?? null),
            'reject'  => $this->service->reject($agentCommission, $user->id, $validated['approval_notes'] ?? ''),
            'pay'     => $this->service->markPaid($agentCommission, $validated['payout_reference'] ?? ''),
            default   => null,
        };

        return back()->with('success', 'Commission status updated.');
    }
}
