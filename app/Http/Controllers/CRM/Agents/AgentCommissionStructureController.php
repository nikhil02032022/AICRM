<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Agents;

use App\Enums\CRM\Agents\CommissionStructureType;
use App\Http\Controllers\Controller;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentCommissionStructure;
use App\Models\CRM\CrmProgramme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AG-004 — Commission structure configuration per agent agreement
final class AgentCommissionStructureController extends Controller
{
    public function index(Agent $agent): View
    {
        $this->authorize('update', $agent);

        $structures = $agent->commissionStructures()
            ->with('programme')
            ->latest('effective_from')
            ->get();

        return view('crm.agents.commission.index', compact('agent', 'structures'));
    }

    public function create(Agent $agent): View
    {
        $this->authorize('update', $agent);

        $programmes  = CrmProgramme::withoutGlobalScopes()
            ->where('institution_id', $agent->institution_id)
            ->pluck('name', 'id');
        $structureTypes = CommissionStructureType::optionsForSelect();

        return view('crm.agents.commission.create', compact('agent', 'programmes', 'structureTypes'));
    }

    public function store(Request $request, Agent $agent): RedirectResponse
    {
        $this->authorize('update', $agent);

        $validated = $request->validate([
            'programme_id'   => ['required', 'integer', 'exists:crm_programmes,id'],
            'structure_type' => ['required', 'in:per_enrolment,per_application,percentage_fee'],
            'amount'         => ['nullable', 'numeric', 'min:0', 'required_if:structure_type,per_enrolment,per_application'],
            'percentage'     => ['nullable', 'numeric', 'min:0', 'max:100', 'required_if:structure_type,percentage_fee'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after:effective_from'],
        ]);

        $validated['agent_id']       = $agent->id;
        $validated['institution_id'] = $agent->institution_id;

        AgentCommissionStructure::create($validated);

        return redirect()->route('crm.agents.commission-structures.index', $agent)
            ->with('success', 'Commission structure added.');
    }

    public function edit(Agent $agent, AgentCommissionStructure $commissionStructure): View
    {
        $this->authorize('update', $agent);

        $programmes  = CrmProgramme::withoutGlobalScopes()
            ->where('institution_id', $agent->institution_id)
            ->pluck('name', 'id');
        $structureTypes = CommissionStructureType::optionsForSelect();

        return view('crm.agents.commission.edit', compact('agent', 'commissionStructure', 'programmes', 'structureTypes'));
    }

    public function update(Request $request, Agent $agent, AgentCommissionStructure $commissionStructure): RedirectResponse
    {
        $this->authorize('update', $agent);

        $validated = $request->validate([
            'programme_id'   => ['required', 'integer', 'exists:crm_programmes,id'],
            'structure_type' => ['required', 'in:per_enrolment,per_application,percentage_fee'],
            'amount'         => ['nullable', 'numeric', 'min:0'],
            'percentage'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after:effective_from'],
        ]);

        $commissionStructure->update($validated);

        return redirect()->route('crm.agents.commission-structures.index', $agent)
            ->with('success', 'Commission structure updated.');
    }
}
