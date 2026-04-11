<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreLeadAttributionTouchpointRequest;
use App\Http\Resources\CRM\LeadAttributionResource;
use App\Models\CRM\Lead;
use App\Services\CRM\Marketing\AttributionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-LC-016 — Integration API for lead attribution timeline and touchpoint ingestion.
final class AttributionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AttributionService $attributionService,
    ) {}

    public function index(Lead $lead): AnonymousResourceCollection
    {
        Gate::authorize('crm.campaigns.manage');

        return LeadAttributionResource::collection($this->attributionService->timelineForLead($lead));
    }

    public function store(StoreLeadAttributionTouchpointRequest $request, Lead $lead): JsonResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $attribution = $this->attributionService->addTouchpoint(
            lead: $lead,
            payload: $request->validated(),
            createdBy: (int) $request->user()->id,
        );

        return $this->success(
            data: new LeadAttributionResource($attribution),
            message: 'Attribution touchpoint captured successfully.',
            status: 201,
        );
    }
}
