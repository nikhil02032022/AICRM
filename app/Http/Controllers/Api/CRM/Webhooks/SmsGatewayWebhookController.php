<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\Communication\ProcessSmsDeliveryJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

// BRD: CRM-CC-008 — SMS delivery receipt webhooks from gateways
final class SmsGatewayWebhookController extends Controller
{
    private const SUPPORTED_GATEWAYS = ['msg91', 'textlocal', 'kaleyra'];

    public function __invoke(Request $request, string $gateway): Response
    {
        if (! in_array($gateway, self::SUPPORTED_GATEWAYS, true)) {
            return response('', Response::HTTP_NOT_FOUND);
        }

        if (! $this->verifySignature($request, $gateway)) {
            Log::warning('SMS webhook signature mismatch', ['gateway' => $gateway]);
            return response('', Response::HTTP_FORBIDDEN);
        }

        // BRD: Return 200 immediately, process asynchronously (< 300ms)
        ProcessSmsDeliveryJob::dispatch($gateway, $request->all())
            ->onQueue('crm-comms-sms');

        return response('', Response::HTTP_OK);
    }

    private function verifySignature(Request $request, string $gateway): bool
    {
        $secret = config("services.sms.{$gateway}.webhook_secret");

        if (empty($secret)) {
            return false;
        }

        $signature = $request->header('X-Webhook-Signature', '');
        $computed  = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($computed, $signature);
    }
}
