<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\TriggerAlumniBridgeRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Integration\AlumniBridgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-EI-008 — Alumni module bridge web controller (session auth, Blade views)
final class AlumniBridgeWebController extends Controller
{
    public function __construct(
        private readonly AlumniBridgeService $service
    ) {}

    /**
     * BRD: CRM-EI-008 — List alumni bridge logs and referral dashboard
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

        return view('crm.integrations.alumni-bridge', compact('logs', 'leads'));
    }

    /**
     * BRD: CRM-EI-008 — Manually trigger alumni bridge for a graduated student
     */
    public function store(TriggerAlumniBridgeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $lead = Lead::where('uuid', $request->validated('lead_uuid'))
            ->where('institution_id', $user->institution_id)
            ->firstOrFail();

        $this->service->trigger(
            leadId:          $lead->id,
            institutionId:   $user->institution_id,
            campusId:        (int) ($user->campus_id ?? 0),
            erpStudentId:    $request->validated('erp_student_id'),
            payloadSummary:  ['triggered_by' => $user->id, 'lead_uuid' => $lead->uuid],
        );

        return back()->with('success', 'Alumni bridge handoff queued successfully.');
    }
}
