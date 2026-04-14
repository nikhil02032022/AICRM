<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreWorkflowTemplateRequest;
use App\Http\Resources\CRM\WorkflowTemplateResource;
use App\Models\CRM\WorkflowTemplate;
use App\Services\CRM\Admin\WorkflowTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-SA-007 — API controller for workflow template library (external integrations)
final class WorkflowTemplateController extends Controller
{
    public function __construct(
        private readonly WorkflowTemplateService $service,
    ) {}

    /** GET /api/v1/crm/workflow-templates */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('crm.settings.workflow-templates.view');

        $templates = $this->service->paginate(
            $request->user()->institution_id,
            $request->only(['category', 'search']),
            (int) $request->input('per_page', 25),
        );

        return WorkflowTemplateResource::collection($templates);
    }

    /** POST /api/v1/crm/workflow-templates */
    public function store(StoreWorkflowTemplateRequest $request): JsonResponse
    {
        Gate::authorize('crm.settings.workflow-templates.manage');

        $template = $this->service->create(
            $request->validated(),
            $request->user()->institution_id,
        );

        return (new WorkflowTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/v1/crm/workflow-templates/{workflowTemplate:uuid} */
    public function show(WorkflowTemplate $workflowTemplate): WorkflowTemplateResource
    {
        Gate::authorize('crm.settings.workflow-templates.view');

        return new WorkflowTemplateResource($workflowTemplate);
    }

    /** PUT /api/v1/crm/workflow-templates/{workflowTemplate:uuid} */
    public function update(StoreWorkflowTemplateRequest $request, WorkflowTemplate $workflowTemplate): WorkflowTemplateResource
    {
        Gate::authorize('crm.settings.workflow-templates.manage');

        $updated = $this->service->update($workflowTemplate, $request->validated());

        return new WorkflowTemplateResource($updated);
    }

    /** DELETE /api/v1/crm/workflow-templates/{workflowTemplate:uuid} */
    public function destroy(WorkflowTemplate $workflowTemplate): JsonResponse
    {
        Gate::authorize('crm.settings.workflow-templates.manage');

        $this->service->delete($workflowTemplate);

        return response()->json(['success' => true, 'message' => 'Template deleted.']);
    }

    /** POST /api/v1/crm/workflow-templates/{workflowTemplate:uuid}/import */
    public function import(WorkflowTemplate $workflowTemplate, Request $request): JsonResponse
    {
        Gate::authorize('crm.settings.workflow-templates.manage');

        $workflow = $this->service->importAsWorkflow(
            $workflowTemplate,
            $request->user()->institution_id,
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'data'    => ['workflow_uuid' => $workflow->uuid],
            'message' => 'Template imported as workflow draft.',
        ]);
    }
}
