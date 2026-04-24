<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web;

use App\Http\Controllers\Controller;
use App\Models\CRM\Campus;
use App\Models\CRM\Institution;
use App\Models\CRM\WalkInToken;
use App\Services\CRM\Counselling\WalkInQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-EC-019 — Counsellor queue management and public display screen
final class WalkInQueueController extends Controller
{
    public function __construct(
        private readonly WalkInQueueService $queueService,
    ) {}

    /** Counsellor queue management dashboard. */
    public function index(Request $request): View
    {
        $user   = $request->user();
        $campus = Campus::find($user->campus_id);

        abort_if($campus === null, 403, 'No campus assigned to your account.');

        return view('crm.walk-in-queue.index', [
            'campus' => $campus,
        ]);
    }

    /** Call the next waiting token for the authenticated counsellor's campus. */
    public function callNext(Request $request): JsonResponse
    {
        $user   = $request->user();
        $campus = Campus::find($user->campus_id);

        abort_if($campus === null, 403, 'No campus assigned to your account.');

        try {
            $token = $this->queueService->callNext($campus, $user);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'token_number' => $token->token_number,
            'status' => $token->status->value,
            'status_label' => $token->status->label(),
        ]);
    }

    /** Mark a token as served. */
    public function serve(Request $request, WalkInToken $token): JsonResponse
    {
        Gate::authorize('manage', $token);

        try {
            $this->queueService->serve($token);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => $token->fresh()?->status->value]);
    }

    /** Mark a token as skipped. */
    public function skip(Request $request, WalkInToken $token): JsonResponse
    {
        Gate::authorize('manage', $token);

        try {
            $this->queueService->skip($token);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => $token->fresh()?->status->value]);
    }

    /**
     * Public TV display screen — unauthenticated.
     * Shows token numbers only; no visitor names or personal data.
     */
    public function display(Institution $institution): View
    {
        abort_unless($institution->is_active, 404);

        return view('crm.walk-in-queue.display', [
            'institution' => $institution,
        ]);
    }

    /** Daily analytics for the authenticated user's campus. */
    public function stats(Request $request): View
    {
        $user   = $request->user();
        $campus = Campus::find($user->campus_id);

        abort_if($campus === null, 403, 'No campus assigned to your account.');

        $stats = $this->queueService->dailyStats($campus);

        return view('crm.walk-in-queue.stats', [
            'campus' => $campus,
            'stats' => $stats,
        ]);
    }
}
