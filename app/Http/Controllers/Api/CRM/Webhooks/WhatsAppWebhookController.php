<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\Communication\ProcessInboundWhatsAppJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

// BRD: CRM-CC-011, CRM-CC-012, CRM-LC-007 — WhatsApp inbound webhook (Meta Cloud API)
final class WhatsAppWebhookController extends Controller
{
    /** GET — Meta webhook verification challenge */
    public function verify(Request $request): Response|JsonResponse
    {
        $mode      = $request->query('hub_mode', '');
        $token     = $request->query('hub_verify_token', '');
        $challenge = $request->query('hub_challenge', '');

        $expectedToken = config('services.whatsapp.meta.verify_token');

        if ($mode === 'subscribe' && hash_equals((string) $expectedToken, (string) $token)) {
            return response((string) $challenge, Response::HTTP_OK);
        }

        return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
    }

    /** POST — Inbound messages and status updates from Meta */
    public function receive(Request $request): Response
    {
        if (! $this->verifyMetaSignature($request)) {
            Log::warning('WhatsApp webhook Meta signature mismatch');
            return response('', Response::HTTP_FORBIDDEN);
        }

        $payload = $request->all();

        // BRD: Return 200 immediately so Meta does not retry; process async
        ProcessInboundWhatsAppJob::dispatch($payload)
            ->onQueue('crm-comms-whatsapp');

        return response('', Response::HTTP_OK);
    }

    private function verifyMetaSignature(Request $request): bool
    {
        $appSecret = config('services.whatsapp.meta.app_secret');

        if (empty($appSecret)) {
            return false;
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $computed  = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($computed, $signature);
    }
}
