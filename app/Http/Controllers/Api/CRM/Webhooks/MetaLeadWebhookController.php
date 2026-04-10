<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\ProcessMetaLeadJob;
use App\Models\CRM\IntegrationCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * MetaLeadWebhookController — Handles Meta Lead Ads webhook integration.
 *
 * Routes:
 *   GET  /api/v1/crm/webhooks/meta/{integration:uuid}  — Webhook verification challenge
 *   POST /api/v1/crm/webhooks/meta/{integration:uuid}  — Lead notification event
 *
 * Auth:
 *   GET  — reads verify_token from IntegrationCredential directly (no signature check needed)
 *   POST — VerifyWebhookSignature middleware (HMAC-SHA256 of raw body with app_secret)
 *
 * BRD: CRM-LC-004 — Meta Lead Ads API auto-import
 * OWASP A01 — verify_token checked directly without exposing it in response
 */
final class MetaLeadWebhookController extends Controller
{
    /**
     * GET /api/v1/crm/webhooks/meta/{integration:uuid}
     *
     * Meta sends a challenge verification when the webhook URL is first registered.
     * Must respond with hub.challenge if verify_token matches stored token.
     */
    public function verify(Request $request, string $integration): JsonResponse|Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge', '');

        if ($mode !== 'subscribe' || empty($token)) {
            return response()->json(['error' => 'Invalid verification request.'], 400);
        }

        // Find credential without scope (no auth in GET challenge context)
        $credential = IntegrationCredential::withoutGlobalScopes()
            ->where('uuid', $integration)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if ($credential === null) {
            return response()->json(['error' => 'Unknown integration.'], 404);
        }

        $storedToken = $credential->getCredential('verify_token');

        // BRD: OWASP A07 — timing-safe comparison
        if (!hash_equals((string) $storedToken, (string) $token)) {
            Log::warning('MetaLeadWebhook: verify_token mismatch', [
                'integration_uuid' => $integration,
            ]);

            return response()->json(['error' => 'Forbidden.'], 403);
        }

        // Return plain text challenge — Meta expects plain text 200 response
        return response($challenge)->header('Content-Type', 'text/plain');
    }

    /**
     * POST /api/v1/crm/webhooks/meta/{integration:uuid}
     *
     * Meta sends a "leadgen" webhook event containing leadgen_id(s).
     * We ACK immediately and process asynchronously via ProcessMetaLeadJob.
     */
    public function receive(Request $request): JsonResponse
    {
        /** @var IntegrationCredential $credential */
        $credential = $request->attributes->get('webhook_credential');

        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json(['status' => 'ignored'], 200);
        }

        Log::info('MetaLeadWebhook: received', [
            'integration_uuid' => $credential->uuid,
            'institution_id' => $credential->institution_id,
            'object' => $payload['object'] ?? 'unknown',
        ]);

        // Extract leadgen_id(s) from the nested Meta webhook structure
        // Payload: { "object": "page", "entry": [{ "changes": [{ "value": { "leadgen_id": "..." } }] }] }
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $leadgenId = $change['value']['leadgen_id'] ?? null;

                if (!empty($leadgenId)) {
                    ProcessMetaLeadJob::dispatch(
                        leadgenId: (string) $leadgenId,
                        integrationUuid: $credential->uuid,
                        institutionId: $credential->institution_id,
                        platformIp: $request->ip() ?? '0.0.0.0',
                    );
                }
            }
        }

        // Meta requires a 200 response to stop retrying
        return response()->json(['status' => 'queued'], 200);
    }
}
