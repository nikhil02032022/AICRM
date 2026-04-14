<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\WorkflowTemplateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreWorkflowTemplateRequest;
use App\Models\CRM\WorkflowTemplate;
use App\Services\CRM\Admin\WorkflowTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-SA-007 — Web controller: workflow automation template library
final class WorkflowTemplateWebController extends Controller
{
    public function __construct(
        private readonly WorkflowTemplateService $service,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.settings.workflow-templates.view');

        $templates = $this->service->paginate(
            $request->user()->institution_id,
            $request->only(['category', 'search']),
        );

        return view('crm.settings.workflow-templates.index', [
            'templates'        => $templates,
            'categoryOptions'  => WorkflowTemplateCategory::optionsForSelect(),
            'currentCategory'  => $request->query('category', ''),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.settings.workflow-templates.manage');

        return view('crm.settings.workflow-templates.create', [
            'categoryOptions' => WorkflowTemplateCategory::optionsForSelect(),
        ]);
    }

    public function store(StoreWorkflowTemplateRequest $request): RedirectResponse
    {
        Gate::authorize('crm.settings.workflow-templates.manage');

        $this->service->create(
            $request->validated(),
            $request->user()->institution_id,
        );

        return redirect()
            ->route('crm.settings.workflow-templates.index')
            ->with('success', 'Template created.');
    }

    public function edit(WorkflowTemplate $workflowTemplate): View
    {
        Gate::authorize('crm.settings.workflow-templates.manage');

        return view('crm.settings.workflow-templates.create', [
            'template'        => $workflowTemplate,
            'categoryOptions' => WorkflowTemplateCategory::optionsForSelect(),
        ]);
    }

    public function update(StoreWorkflowTemplateRequest $request, WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        Gate::authorize('crm.settings.workflow-templates.manage');

        $this->service->update($workflowTemplate, $request->validated());

        return redirect()
            ->route('crm.settings.workflow-templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        Gate::authorize('crm.settings.workflow-templates.manage');

        $this->service->delete($workflowTemplate);

        return redirect()
            ->route('crm.settings.workflow-templates.index')
            ->with('success', 'Template deleted.');
    }

    // BRD: CRM-SA-007 — Import template as a new automation workflow draft
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
            'message' => "'{$workflowTemplate->name}' imported as a workflow draft.",
        ]);
    }
}
