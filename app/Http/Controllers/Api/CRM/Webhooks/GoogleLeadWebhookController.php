<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\ProcessGoogleLeadJob;
use App\Models\CRM\IntegrationCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GoogleLeadWebhookController — Receives Google Lead Form Extensions webhook POSTs.
 *
 * Route: POST /api/v1/crm/webhooks/google/{integration:uuid}
 * Auth:  VerifyWebhookSignature middleware (no Sanctum — platform-to-server call)
 *
 * BRD: CRM-LC-003 — Google Ads Lead Form Extensions webhook auto-import
 * OWASP A01 — Institution scoping is enforced via IntegrationCredential lookup in middleware
 */
final class GoogleLeadWebhookController extends Controller
{
    /**
     * POST /api/v1/crm/webhooks/google/{integration:uuid}
     *
     * Acknowledges immediately (within platform's 5-second SLA),
     * then dispatches ProcessGoogleLeadJob to process asynchronously.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var IntegrationCredential $credential */
        // Resolved and bound by VerifyWebhookSignature middleware
        $credential = $request->attributes->get('webhook_credential');

        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json(['status' => 'ignored', 'reason' => 'empty_payload'], 200);
        }

        // BRD: CRM-CR-002 — No PII in logs
        Log::info('GoogleLeadWebhook: received', [
            'integration_uuid' => $credential->uuid,
            'institution_id'   => $credential->institution_id,
        ]);

        // Dispatch async — must ACK Google within 5 seconds
        ProcessGoogleLeadJob::dispatch(
            payload:       $payload,
            institutionId: $credential->institution_id,
            platformIp:    $request->ip() ?? '0.0.0.0',
        );

        // Google expects a 200 response to acknowledge receipt
        return response()->json(['status' => 'queued'], 200);
    }
}
