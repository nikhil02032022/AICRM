<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreLeadRequest;
use App\Http\Requests\Api\CRM\UpdateLeadRequest;
use App\Models\CRM\Lead;
use App\Models\CRM\ScoreOverride;
use App\Services\CRM\Lead\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

// BRD: CRM-LC-011 — Web controller for Blade views (non-API, web middleware stack)
final class LeadWebController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function index(): View
    {
        return view('crm.leads.index', [
            'sourceOptions' => LeadSource::optionsForSelect(),
        ]);
    }

    /**
     * BRD: CRM-LC-011 — Handle modal form POST via session-authenticated web route.
     * Returns JSON so Alpine.js can handle success and refresh the Livewire table.
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $dto = CreateLeadDTO::fromRequest($request->validated(), $request->ip() ?? '');
        $lead = $this->leadService->create($dto, $request->user());

        return response()->json([
            'success' => true,
            'data' => ['uuid' => $lead->uuid, 'full_name' => trim($lead->first_name.' '.$lead->last_name)],
            'message' => 'Lead created successfully.',
        ], 201);
    }

    /**
     * BRD: CRM-EC-004 — Complete activity timeline and 360° profile displayed on the lead record.
     */
    public function show(Lead $lead): View
    {
        $lead->load(['assignedCounsellor', 'programmeInterests']);

        // Load audit timeline — latest 20 entries with actor name
        $auditLogs = $lead->auditLogs()
            ->with('actor:id,name')
            ->latest('created_at')
            ->limit(20)
            ->get();

        // BRD: CRM-LQ-007 — Score override history for the scoring tab
        $scoreOverrides = ScoreOverride::where('lead_id', $lead->id)
            ->with('overriddenBy:id,name')
            ->latest('created_at')
            ->get();

        $sourceOptions = LeadSource::optionsForSelect();
        $statusOptions = collect(LeadStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();

        return view('crm.leads.show', compact('lead', 'auditLogs', 'scoreOverrides', 'sourceOptions', 'statusOptions'));
    }

    /**
     * BRD: CRM-LC-011 — Handle edit modal PUT via session-authenticated web route.
     * Returns JSON so Alpine.js can handle success and refresh the page.
     */
    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $data = $request->validated();

        $lead = $this->leadService->update($lead, $data);

        return response()->json([
            'success' => true,
            'data' => ['uuid' => $lead->uuid, 'full_name' => trim($lead->first_name.' '.$lead->last_name)],
            'message' => 'Lead updated successfully.',
        ]);
    }

    /**
     * BRD: CRM-LC-011 — Soft-delete via session-authenticated web route.
     * Hard delete is prohibited per BRD data retention rules.
     */
    public function destroy(Lead $lead): JsonResponse
    {
        $this->leadService->delete($lead);

        return response()->json([
            'success' => true,
            'message' => 'Lead archived successfully.',
        ]);
    }
}
