<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreTelecallingCampaignRequest;
use App\Http\Resources\CRM\TelecallingCampaignResource;
use App\Models\CRM\TelecallingCampaign;
use App\Services\CRM\Communication\TelecallingCampaignService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

// BRD: CRM-TC-006 — Integration API for telecalling campaign management
final class TelecallingCampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TelecallingCampaignService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $campaigns = $this->service->paginate($request->only(['status', 'search']), $perPage);

        return $this->success(
            data: TelecallingCampaignResource::collection($campaigns->items()),
            message: 'Telecalling campaigns fetched successfully.',
            meta: [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        );
    }

    public function store(StoreTelecallingCampaignRequest $request): JsonResponse
    {
        Gate::authorize('crm.campaigns.manage');

        try {
            $campaign = $this->service->create(
                institutionId: (int) $request->user()->institution_id,
                createdBy: (int) $request->user()->id,
                payload: $request->validated(),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 'CAMPAIGN_VALIDATION_FAILED', 422);
        }

        return $this->created(
            data: new TelecallingCampaignResource($campaign),
            message: 'Telecalling campaign created successfully.',
        );
    }

    public function show(TelecallingCampaign $telecallingCampaign): JsonResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $telecallingCampaign->loadCount(['agents', 'leads', 'diallerSessions']);

        return $this->success(
            data: [
                'campaign' => new TelecallingCampaignResource($telecallingCampaign),
                'progress' => $this->service->progress($telecallingCampaign),
            ],
            message: 'Telecalling campaign fetched successfully.',
        );
    }

    public function update(StoreTelecallingCampaignRequest $request, TelecallingCampaign $telecallingCampaign): JsonResponse
    {
        Gate::authorize('crm.campaigns.manage');

        try {
            $updated = $this->service->update($telecallingCampaign, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 'CAMPAIGN_VALIDATION_FAILED', 422);
        }

        return $this->success(
            data: new TelecallingCampaignResource($updated),
            message: 'Telecalling campaign updated successfully.',
        );
    }

    public function launch(TelecallingCampaign $telecallingCampaign): JsonResponse
    {
        Gate::authorize('crm.campaigns.manage');

        try {
            $launched = $this->service->launch($telecallingCampaign);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 'CAMPAIGN_LAUNCH_BLOCKED', 422);
        }

        return $this->success(
            data: [
                'campaign' => new TelecallingCampaignResource($launched),
                'progress' => $this->service->progress($launched),
            ],
            message: 'Telecalling campaign launched successfully.',
        );
    }
}
