<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\MergeLeadsApiRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Lead\LeadMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-LC-019 — API merge endpoint (Sanctum auth)
final class LeadMergeController extends Controller
{
    public function __construct(
        private readonly LeadMergeService $mergeService,
    ) {}

    /**
     * POST /api/v1/crm/leads/{lead:uuid}/merge
     *
     * Returns 202 Accepted immediately; actual data transfer is async.
     */
    public function __invoke(MergeLeadsApiRequest $request, Lead $lead): JsonResponse
    {
        Gate::authorize('merge', $lead);

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
     * GET /api/v1/crm/leads/{lead:uuid}/merge-status
     */
    public function status(Request $request, Lead $lead): JsonResponse
    {
        Gate::authorize('view', $lead);

        return response()->json([
            'data' => [
                'is_merged' => $lead->isMerged(),
                'merged_into_uuid' => $lead->merged_into_uuid,
                'merged_at' => $lead->merged_at?->toISOString(),
            ],
        ]);
    }
}
