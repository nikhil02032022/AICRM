<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\Communication\ProcessIvrLeadCreationJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

// BRD: CRM-CC-019, CRM-LC-010 — IVR inbound call callbacks → lead auto-creation
final class IvrWebhookController extends Controller
{
    private const ALLOWED_IP_CONFIG_KEY = 'services.telephony.allowed_ips';

    public function __invoke(Request $request, string $provider): Response
    {
        if (! $this->isAllowedIp($request)) {
            Log::warning('IVR webhook from disallowed IP', [
                'ip'       => $request->ip(),
                'provider' => $provider,
            ]);
            return response('', Response::HTTP_FORBIDDEN);
        }

        $callId = (string) ($request->input('call_sid')
            ?? $request->input('call_id')
            ?? $request->input('sid')
            ?? '');

        if (empty($callId)) {
            Log::warning('IVR webhook missing call identifier', ['provider' => $provider]);
            return response('', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // BRD: Return 200 immediately; LC-010 lead creation processed async
        ProcessIvrLeadCreationJob::dispatch($provider, $callId, $request->all())
            ->onQueue('crm-comms-voice');

        return response('', Response::HTTP_OK);
    }

    private function isAllowedIp(Request $request): bool
    {
        $allowedIps = config(self::ALLOWED_IP_CONFIG_KEY, []);

        if (empty($allowedIps)) {
            return false;
        }

        return in_array($request->ip(), (array) $allowedIps, true);
    }
}
