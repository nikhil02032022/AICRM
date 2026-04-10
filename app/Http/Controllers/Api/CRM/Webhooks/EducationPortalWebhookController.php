<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM\Webhooks;

use App\Enums\CRM\IntegrationChannel;
use App\Http\Controllers\Controller;
use App\Jobs\CRM\ProcessPortalLeadJob;
use App\Models\CRM\IntegrationCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * EducationPortalWebhookController — Handles lead webhooks from education portals.
 *
 * Route: POST /api/v1/crm/webhooks/portal/{channel}/{integration:uuid}
 *   {channel} = shiksha | college_dekho | careers360 | collegedunia
 *
 * Auth: VerifyWebhookSignature middleware (no Sanctum — server-to-server call)
 *
 * BRD: CRM-LC-008 — Education portal imports (Shiksha, CollegeDekho, Careers360, Collegedunia)
 * OWASP A01 — Institution scoping enforced via IntegrationCredential lookup in middleware
 */
final class EducationPortalWebhookController extends Controller
{
    /**
     * POST /api/v1/crm/webhooks/portal/{channel}/{integration:uuid}
     */
    public function __invoke(Request $request, string $channel): JsonResponse
    {
        // Validate channel is a known portal
        $integrationChannel = IntegrationChannel::tryFrom($channel);

        if ($integrationChannel === null || !in_array($integrationChannel, IntegrationChannel::portalCases(), true)) {
            return response()->json(['status' => 'ignored', 'reason' => 'unknown_channel'], 200);
        }

        /** @var IntegrationCredential $credential */
        $credential = $request->attributes->get('webhook_credential');

        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json(['status' => 'ignored', 'reason' => 'empty_payload'], 200);
        }

        Log::info('EducationPortalWebhook: received', [
            'channel' => $channel,
            'integration_uuid' => $credential->uuid,
            'institution_id' => $credential->institution_id,
        ]);

        ProcessPortalLeadJob::dispatch(
            payload: $payload,
            channel: $channel,
            integrationUuid: $credential->uuid,
            institutionId: $credential->institution_id,
            platformIp: $request->ip() ?? '0.0.0.0',
        );

        // Most portals expect a 200 to stop retrying
        return response()->json(['status' => 'queued'], 200);
    }
}
