<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\CreateCampaignSpendDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\IndexCostPerLeadRequest;
use App\Http\Requests\Api\CRM\StoreCampaignSpendRequest;
use App\Services\CRM\Marketing\CostTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-LC-017 — Web controller for campaign spend entry and CPL reporting.
final class CostTrackingWebController extends Controller
{
    public function __construct(
        private readonly CostTrackingService $costTrackingService,
    ) {}

    public function index(IndexCostPerLeadRequest $request): View
    {
        Gate::authorize('crm.campaigns.manage');

        $report = $this->costTrackingService->report(
            filters: $request->validated(),
            perPage: (int) ($request->validated()['per_page'] ?? 20),
        );

        return view('crm.marketing.cost-tracking.index', [
            'report' => $report,
            'filters' => $request->validated(),
        ]);
    }

    public function store(StoreCampaignSpendRequest $request): RedirectResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $this->costTrackingService->createSpend(
            dto: CreateCampaignSpendDTO::fromRequest($request->validated()),
            institutionId: (int) $request->user()->institution_id,
            userId: (int) $request->user()->id,
        );

        return redirect()
            ->route('crm.marketing.cost-tracking.index')
            ->with('success', 'Campaign spend recorded successfully.');
    }
}
