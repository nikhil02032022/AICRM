<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\CheckErpStudentMatchJob;
use App\Models\CRM\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-LC-020 — API endpoint to trigger and query ERP Student Master match
final class ErpMatchController extends Controller
{
    /**
     * POST /api/v1/crm/leads/{lead:uuid}/check-erp
     *
     * Dispatches the ERP student match job. Idempotent — ShouldBeUnique ensures
     * no duplicate job is queued if one is already pending.
     */
    public function __invoke(Request $request, Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.edit');

        CheckErpStudentMatchJob::dispatch($lead->uuid, $lead->institution_id);

        return response()->json([
            'success' => true,
            'message' => 'ERP Student Master check queued.',
        ], 202);
    }

    /**
     * GET /api/v1/crm/leads/{lead:uuid}/erp-match
     *
     * Returns the current ERP match state for a lead.
     */
    public function show(Request $request, Lead $lead): JsonResponse
    {
        Gate::authorize('crm.leads.view');

        return response()->json([
            'data' => [
                'erp_match_status'  => $lead->erp_match_status?->value,
                'erp_student_uuid'  => $lead->erp_student_uuid,
                'is_matched'        => $lead->erp_student_uuid !== null,
            ],
        ]);
    }
}
