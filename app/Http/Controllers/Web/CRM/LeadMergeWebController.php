<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CRM\MergeLeadsRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Lead\LeadMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// BRD: CRM-LC-019 — Web-authenticated merge endpoint for the lead show page
final class LeadMergeWebController extends Controller
{
    public function __construct(
        private readonly LeadMergeService $mergeService,
    ) {}

    /**
     * POST /crm/leads/{lead:uuid}/merge
     *
     * Validates and dispatches the merge job. Returns 202 with a merge reference token.
     * The UI should poll /merge-status or reload after a short delay.
     */
    public function __invoke(MergeLeadsRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('merge', $lead);

        $secondaryUuid = $request->validated('secondary_uuid');

        $secondary = Lead::withoutGlobalScopes()
            ->where('uuid', $secondaryUuid)
            ->where('institution_id', $lead->institution_id)
            ->firstOrFail();

        try {
            $mergeRef = $this->mergeService->dispatch($lead, $secondary, $request->user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Merge queued. The primary lead will be updated shortly.',
            'data' => [
                'primary_uuid' => $lead->uuid,
                'secondary_uuid' => $secondaryUuid,
                'merge_ref' => $mergeRef,
            ],
        ], 202);
    }

    /**
     * GET /crm/leads/{lead:uuid}/merge-status
     *
     * Returns the current merge state for the lead (polling endpoint).
     */
    public function status(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        return response()->json([
            'data' => [
                'is_merged' => $lead->isMerged(),
                'merged_into_uuid' => $lead->merged_into_uuid,
                'merged_at' => $lead->merged_at?->toISOString(),
            ],
        ]);
    }
}
