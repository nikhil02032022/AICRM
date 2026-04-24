<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web;

use App\Http\Controllers\Controller;
use App\Models\CRM\Campus;
use App\Models\CRM\Institution;
use App\Services\CRM\Counselling\WalkInQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// BRD: CRM-EC-019 — Public kiosk endpoint for walk-in token issuance (no auth required)
final class WalkInKioskController extends Controller
{
    public function __construct(
        private readonly WalkInQueueService $queueService,
    ) {}

    /**
     * Issue a walk-in token from the kiosk.
     * Campus is resolved from the first active campus for the institution.
     * Visitor fields are optional — token is always issued regardless.
     */
    public function issue(Request $request, Institution $institution): JsonResponse
    {
        abort_unless($institution->is_active, 404);

        $validated = $request->validate([
            'campus_id' => ['nullable', 'integer'],
            'visitor_name' => ['nullable', 'string', 'max:100'],
            'visitor_mobile' => ['nullable', 'string', 'max:15', 'regex:/^[6-9]\d{9}$/'],
            'programme_interest' => ['nullable', 'string', 'max:150'],
        ]);

        $campusId = $validated['campus_id'] ?? null;
        $campus   = $campusId
            ? Campus::where('institution_id', $institution->id)->where('id', $campusId)->first()
            : Campus::where('institution_id', $institution->id)->where('is_active', true)->first();

        abort_if($campus === null, 422, 'No active campus found for this institution.');

        $token = $this->queueService->issueToken($campus, [
            'visitor_name' => $validated['visitor_name'] ?? null,
            'visitor_mobile' => $validated['visitor_mobile'] ?? null,
            'programme_interest' => $validated['programme_interest'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'token_number' => $token->token_number,
            'message' => "Your token number is {$token->token_number}. Please wait to be called.",
        ], 201);
    }
}
