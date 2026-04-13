<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\CreateAutomationWorkflowDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreAutomationWorkflowRequest;
use App\Http\Requests\Api\CRM\UpdateAutomationWorkflowRequest;
use App\Models\CRM\AutomationWorkflow;
use App\Repositories\CRM\Marketing\AutomationWorkflowRepositoryInterface;
use App\Services\CRM\Marketing\AutomationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-MA-001 — Session-authenticated CRM controller for automation workflow builder
final class AutomationWorkflowWebController extends Controller
{
    public function __construct(
        private readonly AutomationWorkflowService $service,
        private readonly AutomationWorkflowRepositoryInterface $repository,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.campaigns.manage');

        return view('crm.marketing.automation-workflows.index', [
            'workflows' => $this->repository->paginate(
                filters: $request->only(['search', 'status', 'trigger_type']),
                perPage: 20,
            ),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.campaigns.manage');

        return view('crm.marketing.automation-workflows.edit', [
            'workflow' => null,
        ]);
    }

    public function store(StoreAutomationWorkflowRequest $request): RedirectResponse
    {
        $user = $request->user();

        $workflow = $this->service->create(
            CreateAutomationWorkflowDTO::fromRequest($request->validated()),
            (int) $user->institution_id,
            (int) $user->id,
        );

        return redirect()
            ->route('crm.marketing.automation-workflows.edit', $workflow->uuid)
            ->with('success', 'Automation workflow created successfully.');
    }

    public function edit(AutomationWorkflow $automationWorkflow): View
    {
        Gate::authorize('crm.campaigns.manage');

        $automationWorkflow->loadMissing(['steps', 'creator']);

        return view('crm.marketing.automation-workflows.edit', [
            'workflow' => $automationWorkflow,
        ]);
    }

    public function update(UpdateAutomationWorkflowRequest $request, AutomationWorkflow $automationWorkflow): RedirectResponse
    {
        $workflow = $this->service->update($automationWorkflow, $request->validated());

        return redirect()
            ->route('crm.marketing.automation-workflows.edit', $workflow->uuid)
            ->with('success', 'Automation workflow updated successfully.');
    }

    public function destroy(AutomationWorkflow $automationWorkflow): RedirectResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $this->service->delete($automationWorkflow);

        return redirect()
            ->route('crm.marketing.automation-workflows.index')
            ->with('success', 'Automation workflow deleted successfully.');
    }
}
