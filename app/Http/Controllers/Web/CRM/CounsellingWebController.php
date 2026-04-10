<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\UpdateAssignmentConfigDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CRM\StoreAssignLeadRequest;
use App\Http\Requests\Web\CRM\UpdateAssignmentConfigRequest;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Counselling\CounsellorAssignmentConfigRepositoryInterface;
use App\Services\CRM\Counselling\CounsellorAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-EC-006 — Auto/manual assignment management
// BRD: CRM-EC-007 — Manual reassignment endpoint
// BRD: CRM-EC-008 — Workload dashboard
final class CounsellingWebController extends Controller
{
    public function __construct(
        private readonly CounsellorAssignmentService $assignmentService,
        private readonly CounsellorAssignmentConfigRepositoryInterface $configRepository,
    ) {}

    /**
     * GET /crm/settings/assignment-config
     * BRD: CRM-EC-006 — Institution admin configures assignment mode and thresholds.
     */
    public function assignmentConfig(): View
    {
        Gate::authorize('crm.settings.manage');

        $config = $this->configRepository->getOrCreateForInstitution(
            auth()->user()->institution_id
        );

        return view('crm.counselling.config', compact('config'));
    }

    /**
     * POST /crm/settings/assignment-config
     * BRD: CRM-EC-006 — Save assignment configuration.
     */
    public function updateAssignmentConfig(UpdateAssignmentConfigRequest $request): RedirectResponse
    {
        Gate::authorize('crm.settings.manage');

        $config = $this->configRepository->getOrCreateForInstitution(
            $request->user()->institution_id
        );

        $this->configRepository->update(
            $config,
            UpdateAssignmentConfigDTO::fromRequest($request->validated()),
        );

        return redirect()
            ->route('crm.assignment.config')
            ->with('success', 'Assignment configuration updated.');
    }

    /**
     * POST /crm/leads/{uuid}/assign
     * BRD: CRM-EC-007 — Admissions manager reassigns a lead to a different counsellor.
     * Returns JSON so the Lead show page Alpine.js can refresh.
     */
    public function assignCounsellor(StoreAssignLeadRequest $request, Lead $lead): JsonResponse
    {
        Gate::authorize('assign', $lead);

        $this->assignmentService->manualAssign(
            $lead,
            (int) $request->validated('counsellor_id'),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Lead reassigned successfully.',
        ]);
    }

    /**
     * GET /crm/counsellors/workload
     * BRD: CRM-EC-008 — Counsellor workload dashboard (hosts Livewire component).
     */
    public function workloadDashboard(): View
    {
        Gate::authorize('crm.leads.view');

        return view('crm.counselling.workload');
    }
}
