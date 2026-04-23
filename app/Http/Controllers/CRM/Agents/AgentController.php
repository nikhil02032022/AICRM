<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Agents;

use App\Enums\CRM\Agents\AgentStatus;
use App\Http\Controllers\Controller;
use App\Models\CRM\Agents\Agent;
use App\Services\CRM\Agents\AgentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AG-001 — Agent profile CRUD for admissions managers
final class AgentController extends Controller
{
    public function __construct(private readonly AgentService $agentService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Agent::class);

        $agents = $this->agentService->list(
            $request->user()->institution_id,
            $request->only(['status', 'search']),
        );

        return view('crm.agents.index', compact('agents'));
    }

    public function create(): View
    {
        $this->authorize('create', Agent::class);

        $statuses = AgentStatus::optionsForSelect();

        return view('crm.agents.create', compact('statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Agent::class);

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255'],
            'mobile'          => ['nullable', 'string', 'max:20'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'agreement_start' => ['required', 'date'],
            'agreement_end'   => ['nullable', 'date', 'after:agreement_start'],
            'status'          => ['required', 'in:active,inactive,suspended'],
            'notes'           => ['nullable', 'string'],
        ]);

        $validated['institution_id'] = $request->user()->institution_id;

        $this->agentService->create($validated);

        return redirect()->route('crm.agents.index')
            ->with('success', 'Agent created and referral code generated.');
    }

    public function edit(Agent $agent): View
    {
        $this->authorize('update', $agent);

        $statuses = AgentStatus::optionsForSelect();

        return view('crm.agents.edit', compact('agent', 'statuses'));
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $this->authorize('update', $agent);

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255'],
            'mobile'          => ['nullable', 'string', 'max:20'],
            'password'        => ['nullable', 'string', 'min:8', 'confirmed'],
            'agreement_start' => ['required', 'date'],
            'agreement_end'   => ['nullable', 'date', 'after:agreement_start'],
            'status'          => ['required', 'in:active,inactive,suspended'],
            'notes'           => ['nullable', 'string'],
        ]);

        $this->agentService->update($agent, $validated);

        return redirect()->route('crm.agents.index')
            ->with('success', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $this->authorize('delete', $agent);

        $this->agentService->deactivate($agent);

        return redirect()->route('crm.agents.index')
            ->with('success', 'Agent deactivated.');
    }
}
