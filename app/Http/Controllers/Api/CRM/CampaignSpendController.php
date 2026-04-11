<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\DTOs\CRM\CreateCampaignSpendDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\IndexCostPerLeadRequest;
use App\Http\Requests\Api\CRM\StoreCampaignSpendRequest;
use App\Http\Resources\CRM\CampaignSpendResource;
use App\Services\CRM\Marketing\CostTrackingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-LC-017 — Integration API for campaign spend management and CPL reporting.
final class CampaignSpendController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CostTrackingService $costTrackingService,
    ) {}

    public function index(IndexCostPerLeadRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('crm.campaigns.manage');

        $report = $this->costTrackingService->report(
            filters: $request->validated(),
            perPage: (int) ($request->validated()['per_page'] ?? 20),
        );

        return CampaignSpendResource::collection($report);
    }

    public function store(StoreCampaignSpendRequest $request): JsonResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $spend = $this->costTrackingService->createSpend(
            dto: CreateCampaignSpendDTO::fromRequest($request->validated()),
            institutionId: (int) $request->user()->institution_id,
            userId: (int) $request->user()->id,
        );

        return $this->success(
            data: new CampaignSpendResource($spend),
            message: 'Campaign spend recorded successfully.',
            status: 201,
        );
    }
}
