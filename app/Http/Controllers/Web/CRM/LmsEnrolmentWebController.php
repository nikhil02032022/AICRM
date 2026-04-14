<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\TriggerLmsEnrolmentRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Integration\LmsEnrolmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-EI-010 — LMS enrolment web controller (session auth, Blade views)
final class LmsEnrolmentWebController extends Controller
{
    public function __construct(
        private readonly LmsEnrolmentService $service
    ) {}

    /**
     * BRD: CRM-EI-010 — List LMS enrolment logs with status and error tracking
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $logs = $this->service->list($user->institution_id);
        $leads = Lead::query()
            ->where('institution_id', $user->institution_id)
            ->select(['uuid', 'first_name', 'last_name', 'mobile'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(200)
            ->get();

        return view('crm.integrations.lms-enrolment', compact('logs', 'leads'));
    }

    /**
     * BRD: CRM-EI-010 — Manually trigger LMS enrolment for an admitted student
     */
    public function store(TriggerLmsEnrolmentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $lead = Lead::where('uuid', $request->validated('lead_uuid'))
            ->where('institution_id', $user->institution_id)
            ->firstOrFail();

        $validated = $request->validated();

        $this->service->trigger(
            leadId:        $lead->id,
            institutionId: $user->institution_id,
            campusId:      (int) ($user->campus_id ?? 0),
            erpStudentId:  $validated['erp_student_id'],
            lmsProvider:   $validated['lms_provider'],
            lmsCourseId:   $validated['lms_course_id'],
        );

        return back()->with('success', 'LMS enrolment queued successfully.');
    }
}
