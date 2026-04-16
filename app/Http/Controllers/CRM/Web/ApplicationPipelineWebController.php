<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web;

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\LeadSource;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Repositories\CRM\Application\ApplicationRepositoryInterface;
use App\Services\CRM\Application\ApplicationPipelineService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// BRD: CRM-AP-008, CRM-AP-009, CRM-AP-010 — Application pipeline web views
final class ApplicationPipelineWebController
{
    /**
     * Display Kanban board view of application pipeline.
     * BRD: CRM-AP-008
     */
    public function boardView(Request $request): View
    {
        Gate::authorize('crm.applications.view');

        return view('crm.applications.pipeline.board', [
            'title' => 'Application Pipeline — Kanban View',
        ]);
    }

    /**
     * Display list/table view of applications.
     * BRD: CRM-AP-008, CRM-AP-009, CRM-AP-010
     */
    public function listView(Request $request): View
    {
        Gate::authorize('crm.applications.view');

        /** @var ApplicationRepositoryInterface $repository */
        $repository = app(ApplicationRepositoryInterface::class);

        $filters = array_filter([
            'programme_id' => $request->query('programme_id'),
            'batch' => $request->query('batch'),
            'source' => $request->query('source'),
            'status' => $request->query('status'),
            'assigned_counsellor_id' => $request->query('counsellor_id'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'score_min' => $request->query('score_min'),
            'score_max' => $request->query('score_max'),
            'search' => $request->query('q'),
        ], static fn ($value): bool => $value !== null && $value !== '');

        $applications = $repository->paginate($filters, 20)->withQueryString();
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $institutionId = (int) $currentUser->institution_id;
        $counsellors = User::query()
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $programmes = CrmProgramme::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('crm.applications.pipeline.list', [
            'title' => 'Applications — List View',
            'applications' => $applications,
            'filters' => $filters,
            'statuses' => ApplicationStatus::cases(),
            'leadSources' => LeadSource::cases(),
            'counsellors' => $counsellors,
            'programmes' => $programmes,
        ]);
    }

    /**
     * Display single application detail page.
     * BRD: CRM-AP-008
     */
    public function show(string $uuid): View
    {
        $application = Application::whereUuid($uuid)->firstOrFail();
        Gate::authorize('crm.applications.view', $application);

        return view('crm.applications.pipeline.show', [
            'application' => $application->load(['lead', 'draft', 'assignedCounsellor', 'offerLetters', 'statusHistory']),
        ]);
    }

    /**
     * Render transition action form in a modal or inline.
     * BRD: CRM-AP-009
     */
    public function transitionForm(string $uuid): View
    {
        $application = Application::whereUuid($uuid)->firstOrFail();
        Gate::authorize('transition', $application);

        return view('crm.applications.pipeline.modals.transition-form', [
            'application' => $application,
        ]);
    }

    /**
     * Execute status transition for an application.
     * BRD: CRM-AP-009
     */
    public function transition(string $uuid, Request $request, ApplicationPipelineService $pipelineService): RedirectResponse
    {
        $application = Application::whereUuid($uuid)->firstOrFail();
        Gate::authorize('transition', $application);

        $validated = $request->validate([
            'status' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $newStatus = ApplicationStatus::from($validated['status']);
        } catch (\ValueError) {
            return back()->withErrors([
                'status' => 'Invalid application status value.',
            ]);
        }

        try {
            $changedByUserId = Auth::id();

            $pipelineService->transition(
                $application,
                $newStatus,
                $changedByUserId,
                $validated['reason'] ?? null
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('crm.applications.show', ['application' => $application->uuid])
            ->with('success', 'Application status updated successfully.');
    }
}
