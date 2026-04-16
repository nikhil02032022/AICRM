<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web;

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\LeadSource;
use App\Http\Requests\Web\CRM\BulkApplicationAssignRequest;
use App\Http\Requests\Web\CRM\BulkApplicationCommunicationRequest;
use App\Http\Requests\Web\CRM\BulkApplicationExportRequest;
use App\Http\Requests\Web\CRM\BulkApplicationStatusRequest;
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
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    /**
     * BRD: CRM-AP-010 — Bulk status update from list view.
     */
    public function bulkStatus(BulkApplicationStatusRequest $request, ApplicationPipelineService $pipelineService): RedirectResponse
    {
        Gate::authorize('crm.applications.edit');

        $validated = $request->validated();

        $result = $pipelineService->bulkUpdateStatus(
            $validated['application_uuids'],
            ApplicationStatus::from($validated['status']),
            Auth::id(),
            $validated['reason'] ?? null,
        );

        return back()->with('success', "Bulk status update complete. Updated: {$result['updated']}, Skipped: {$result['skipped']}.");
    }

    /**
     * BRD: CRM-AP-010 — Bulk assign counsellor from list view.
     */
    public function bulkAssign(BulkApplicationAssignRequest $request, ApplicationPipelineService $pipelineService): RedirectResponse
    {
        Gate::authorize('crm.applications.edit');

        $validated = $request->validated();
        $updated = $pipelineService->bulkAssignCounsellor(
            $validated['application_uuids'],
            (int) $validated['counsellor_id'],
        );

        return back()->with('success', "Bulk counsellor assignment complete. Updated: {$updated}.");
    }

    /**
     * BRD: CRM-AP-010 — Bulk send communication from list view.
     */
    public function bulkCommunication(BulkApplicationCommunicationRequest $request, ApplicationPipelineService $pipelineService): RedirectResponse
    {
        Gate::authorize('crm.communication.send');

        $result = $pipelineService->bulkSendCommunication(
            $request->validated()['application_uuids'],
            $request->validated(),
        );

        return back()->with('success', "Bulk communication dispatched. Sent: {$result['sent']}, Skipped: {$result['skipped']}.");
    }

    /**
     * BRD: CRM-AP-010 — Bulk export selected applications.
     */
    public function bulkExport(BulkApplicationExportRequest $request, ApplicationPipelineService $pipelineService): RedirectResponse|StreamedResponse
    {
        Gate::authorize('crm.applications.view');

        $validated = $request->validated();
        $rows = $pipelineService->buildExportRows($validated['application_uuids']);
        $format = $validated['format'] ?? 'csv';

        if ($format === 'json') {
            return back()->with('bulk_export_json', json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $filename = 'applications-export-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(static function () use ($rows): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fputcsv($stream, [
                'application_uuid',
                'lead_uuid',
                'applicant_name',
                'applicant_email',
                'source',
                'lead_score',
                'status',
                'assigned_counsellor',
                'submitted_at',
            ]);

            foreach ($rows as $row) {
                fputcsv($stream, [
                    $row['application_uuid'],
                    $row['lead_uuid'],
                    $row['applicant_name'],
                    $row['applicant_email'],
                    $row['source'],
                    $row['lead_score'],
                    $row['status'],
                    $row['assigned_counsellor'],
                    $row['submitted_at'],
                ]);
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
