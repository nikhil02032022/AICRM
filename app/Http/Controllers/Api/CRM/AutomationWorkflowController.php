<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\DTOs\CRM\CreateAutomationWorkflowDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\IndexAutomationPerformanceReportRequest;
use App\Http\Requests\Api\CRM\StoreAutomationWorkflowRequest;
use App\Http\Requests\Api\CRM\UpdateAutomationWorkflowRequest;
use App\Http\Resources\CRM\AutomationWorkflowResource;
use App\Models\CRM\AutomationWorkflow;
use App\Repositories\CRM\Marketing\AutomationWorkflowRepositoryInterface;
use App\Services\CRM\Marketing\AutomationPerformanceReportService;
use App\Services\CRM\Marketing\AutomationWorkflowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-MA-001 — Integration API controller for automation workflow definitions
final class AutomationWorkflowController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AutomationWorkflowService $service,
        private readonly AutomationWorkflowRepositoryInterface $repository,
        private readonly AutomationPerformanceReportService $performanceReportService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('crm.campaigns.manage');

        $workflows = $this->repository->paginate(
            filters: $request->only(['search', 'status', 'trigger_type']),
            perPage: (int) $request->get('per_page', 20),
        );

        return AutomationWorkflowResource::collection($workflows);
    }

    public function store(StoreAutomationWorkflowRequest $request): JsonResponse
    {
        $user = $request->user();

        $workflow = $this->service->create(
            CreateAutomationWorkflowDTO::fromRequest($request->validated()),
            (int) $user->institution_id,
            (int) $user->id,
        );

        return $this->success(
            new AutomationWorkflowResource($workflow->fresh(['steps', 'creator'])),
            'Automation workflow created successfully.',
            201,
        );
    }

    public function show(AutomationWorkflow $automationWorkflow): AutomationWorkflowResource
    {
        Gate::authorize('crm.campaigns.manage');

        return new AutomationWorkflowResource($automationWorkflow->loadMissing(['steps', 'creator']));
    }

    public function update(UpdateAutomationWorkflowRequest $request, AutomationWorkflow $automationWorkflow): JsonResponse
    {
        $workflow = $this->service->update($automationWorkflow, $request->validated());

        return $this->success(
            new AutomationWorkflowResource($workflow),
            'Automation workflow updated successfully.',
        );
    }

    public function destroy(AutomationWorkflow $automationWorkflow): JsonResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $this->service->delete($automationWorkflow);

        return $this->success(null, 'Automation workflow deleted successfully.');
    }

    // BRD: CRM-MA-010 — Performance reporting for automation workflows.
    public function performanceReport(IndexAutomationPerformanceReportRequest $request): JsonResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $validated = $request->validated();

        $report = $this->performanceReportService->buildReport(
            institutionId: (int) $request->user()->institution_id,
            days: (int) ($validated['days'] ?? 30),
            workflowUuid: isset($validated['workflow_uuid']) ? (string) $validated['workflow_uuid'] : null,
        );

        return $this->success(
            data: $report,
            message: 'Automation performance report generated successfully.',
        );
    }
}
