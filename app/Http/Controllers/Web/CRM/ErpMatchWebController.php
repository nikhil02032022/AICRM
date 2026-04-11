<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\CheckErpStudentMatchJob;
use App\Models\CRM\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// BRD: CRM-LC-020 — Web-authenticated endpoint to manually trigger ERP Student Master check
final class ErpMatchWebController extends Controller
{
    /**
     * POST /crm/leads/{lead:uuid}/check-erp
     *
     * Dispatches the ERP student match job for the given lead.
     * Returns 202 immediately — the result is polled or surfaced via the UI badge refresh.
     */
    public function __invoke(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('crm.leads.edit');

        CheckErpStudentMatchJob::dispatch($lead->uuid, $lead->institution_id);

        return response()->json([
            'success' => true,
            'message' => 'ERP Student Master check queued.',
        ], 202);
    }
}
