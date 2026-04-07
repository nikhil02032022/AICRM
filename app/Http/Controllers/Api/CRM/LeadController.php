<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\DTOs\CRM\CreateLeadDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreLeadRequest;
use App\Http\Requests\Api\CRM\UpdateLeadRequest;
use App\Http\Resources\CRM\LeadResource;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Lead\LeadRepositoryInterface;
use App\Services\CRM\Lead\LeadService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-LC-011 — Lead CRUD via REST API
// Route model binding uses uuid column (never auto-increment id)
final class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService             $leadService,
        private readonly LeadRepositoryInterface $repository,
    ) {}

    /**
     * GET /api/v1/crm/leads
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('crm.leads.view');

        $leads = $this->repository->paginate(
            filters:  $request->only(['status', 'temperature', 'source', 'assigned_counsellor_id', 'search', 'sort', 'direction']),
            perPage:  (int) $request->input('per_page', 25),
        );

        return LeadResource::collection($leads);
    }

    /**
     * POST /api/v1/crm/leads
     *
     * BRD: CRM-LC-011 — Manual lead creation
     * BRD: CRM-LC-014 — source validated as required in StoreLeadRequest
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $dto  = CreateLeadDTO::fromRequest($request->validated(), $request->ip() ?? '');
        $lead = $this->leadService->create($dto, $request->user());

        return (new LeadResource($lead))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.crm.leads.show', ['lead' => $lead->uuid]));
    }

    /**
     * GET /api/v1/crm/leads/{lead:uuid}
     */
    public function show(Lead $lead): LeadResource
    {
        Gate::authorize('crm.leads.view', $lead);

        $lead->load(['assignedCounsellor', 'programmeInterests']);

        return new LeadResource($lead);
    }

    /**
     * PUT /api/v1/crm/leads/{lead:uuid}
     */
    public function update(UpdateLeadRequest $request, Lead $lead): LeadResource
    {
        Gate::authorize('crm.leads.edit', $lead);

        $data = $request->validated();

        // Status transitions go through the service for business rule enforcement
        if (isset($data['status'])) {
            $newStatus = \App\Enums\CRM\LeadStatus::from($data['status']);
            $this->leadService->transitionStatus($lead, $newStatus);
            unset($data['status']);
        }

        if (! empty($data)) {
            $lead = $this->leadService->update($lead, $data);
        }

        if (isset($request->validated()['programme_ids'])) {
            $this->repository->syncProgrammeInterests($lead, $request->validated()['programme_ids'] ?? []);
        }

        return new LeadResource($lead->load(['assignedCounsellor', 'programmeInterests']));
    }

    /**
     * DELETE /api/v1/crm/leads/{lead:uuid}
     *
     * Soft-delete only — hard delete is prohibited per BRD data retention rules.
     */
    public function destroy(Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.delete', $lead);

        $this->leadService->delete($lead);

        return response()->json([
            'success' => true,
            'message' => 'Lead archived successfully.',
        ]);
    }
}
