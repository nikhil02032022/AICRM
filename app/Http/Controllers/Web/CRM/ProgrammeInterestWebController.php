<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\ProgrammeInterestStatus;
use App\Http\Controllers\Controller;
use App\Models\CRM\Lead;
use App\Models\CRM\CrmProgramme;
use App\Services\CRM\Lead\LeadService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// BRD: CRM-EC-002 — Manage per-programme status for a lead
final class ProgrammeInterestWebController extends Controller
{
    public function __construct(private readonly LeadService $leadService) {}

    /**
     * Show the edit form for a programme interest (modal or inline)
     */
    public function edit(Lead $lead, CrmProgramme $programme): View
    {
        $pivot = $lead->programmeInterests()->where('crm_programme_id', $programme->id)->first()?->pivot;
        return view('crm.leads._partials.programme-interest-edit', [
            'lead' => $lead,
            'programme' => $programme,
            'pivot' => $pivot,
            'statusOptions' => ProgrammeInterestStatus::optionsForSelect(),
        ]);
    }

    /**
     * Update the status/notes/intake for a programme interest
     */
    public function update(Request $request, Lead $lead, CrmProgramme $programme): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'preferred_intake' => ['nullable', 'string', 'max:100'],
        ]);
        $this->leadService->updateProgrammeInterest($lead, $programme->id, $data);
        return redirect()->route('crm.leads.show', $lead->uuid)->with('success', 'Programme interest updated.');
    }
}
