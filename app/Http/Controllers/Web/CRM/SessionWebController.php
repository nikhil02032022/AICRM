<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\BookSessionDTO;
use App\DTOs\CRM\UpdateSessionDTO;
use App\Enums\CRM\CounsellingSessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CRM\BookSessionRequest;
use App\Http\Requests\Web\CRM\UpdateSessionRequest;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Counselling\CounsellingService;
use App\Services\CRM\Counselling\CounsellorAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-EC-015 — Internal session booking, outcome recording, and cancellation
final class SessionWebController extends Controller
{
    public function __construct(
        private readonly CounsellingService $counsellingService,
        private readonly CounsellorAvailabilityService $availabilityService,
    ) {}

    /**
     * GET /crm/leads/{lead:uuid}/sessions
     * BRD: CRM-EC-015 — List sessions for a lead (tab on lead show page).
     */
    public function index(Lead $lead): View
    {
        Gate::authorize('view', $lead);

        $sessions = $lead->sessions()->with('counsellor:id,name')->orderByDesc('scheduled_at')->paginate(10);

        return view('crm.sessions.index', compact('lead', 'sessions'));
    }

    /**
     * GET /crm/leads/{lead:uuid}/sessions/create
     * BRD: CRM-EC-015 — Session booking form.
     */
    public function create(Lead $lead, Request $request): View
    {
        Gate::authorize('create', CounsellingSession::class);

        $counsellorId = (int) $request->query('counsellor_id', 0);
        $date = $request->query('date', today()->toDateString());

        $availableTimes = $counsellorId > 0
            ? $this->availabilityService->getAvailableTimesForDate($counsellorId, Carbon::parse($date))
            : collect();

        $counsellors = User::query()
            ->where('institution_id', (int) $request->user()->institution_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $followUpPrompt = $request->session()->get('follow_up_prompt');

        return view('crm.sessions.create', compact('lead', 'counsellorId', 'date', 'availableTimes', 'counsellors', 'followUpPrompt'));
    }

    /**
     * POST /crm/leads/{lead:uuid}/sessions
     * BRD: CRM-EC-015 — Book a new session.
     */
    public function store(BookSessionRequest $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('create', CounsellingSession::class);

        $dto = BookSessionDTO::fromValidated(
            array_merge($request->validated(), ['lead_id' => $lead->getKey()])
        );

        $this->counsellingService->book($dto);

        return redirect()
            ->route('crm.leads.show', $lead)
            ->with('success', 'Session booked successfully.');
    }

    /**
     * PUT /crm/sessions/{session}
     * BRD: CRM-EC-015 — Record session outcome.
     */
    public function update(UpdateSessionRequest $request, CounsellingSession $session): JsonResponse
    {
        Gate::authorize('update', $session);

        $updated = $this->counsellingService->updateOutcome(
            $session,
            UpdateSessionDTO::fromValidated($request->validated()),
        );

        return response()->json([
            'success' => true,
            'status' => $updated->status->value,
            'label' => $updated->status->label(),
        ]);
    }

    /**
     * DELETE /crm/sessions/{session}
     * BRD: CRM-EC-015 — Cancel a session.
     */
    public function destroy(CounsellingSession $session): JsonResponse
    {
        Gate::authorize('cancel', $session);

        $this->counsellingService->updateOutcome(
            $session,
            new UpdateSessionDTO(
                status: CounsellingSessionStatus::CANCELLED,
            ),
        );

        return response()->json(['success' => true]);
    }
}
