<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\Communication\ProcessTelephonyWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

// BRD: CRM-CC-017, CRM-CC-018 — Telephony call status callbacks
final class TelephonyWebhookController extends Controller
{
    private const ALLOWED_IP_CONFIG_KEY = 'services.telephony.allowed_ips';

    public function __invoke(Request $request, string $provider): Response
    {
        if (! $this->isAllowedIp($request)) {
            Log::warning('Telephony webhook from disallowed IP', [
                'ip'       => $request->ip(),
                'provider' => $provider,
            ]);
            return response('', Response::HTTP_FORBIDDEN);
        }

        // BRD: Return 200 immediately; process async
        ProcessTelephonyWebhookJob::dispatch($provider, $request->all())
            ->onQueue('crm-comms-voice');

        return response('', Response::HTTP_OK);
    }

    private function isAllowedIp(Request $request): bool
    {
        $allowedIps = config(self::ALLOWED_IP_CONFIG_KEY, []);

        if (empty($allowedIps)) {
            // No IP allow-list configured — fail closed for security
            return false;
        }

        return in_array($request->ip(), (array) $allowedIps, true);
    }
}
