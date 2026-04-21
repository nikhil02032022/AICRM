<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Api\Payments;

use App\Enums\CRM\Payments\GatewayProvider;
use App\Http\Controllers\Controller;
use App\Models\CRM\Payments\PaymentWebhookEvent;
use App\Services\CRM\Payments\Gateways\PaymentGatewayManager;
use App\Services\CRM\Payments\PaymentWebhookService;
use App\Services\CRM\Payments\Support\PayloadRedactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// BRD: CRM-FM-005 — Inbound gateway webhook endpoint (signature-verified, idempotent).
class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentWebhookService $service,
    ) {}

    public function __invoke(Request $request, string $gateway): JsonResponse
    {
        $provider = GatewayProvider::tryFrom($gateway);
        if ($provider === null) {
            return response()->json(['message' => 'Unknown gateway.'], 404);
        }

        $adapter = $this->gateways->driver($provider);

        $raw = $request->getContent();
        $headers = collect($request->headers->all())
            ->mapWithKeys(fn ($v, $k) => [strtolower($k) => is_array($v) ? ($v[0] ?? '') : $v])
            ->all();

        if (! $adapter->verifySignature($raw, $headers)) {
            PaymentWebhookEvent::create([
                'gateway'         => $provider->value,
                'event_id'        => 'invalid_'.bin2hex(random_bytes(8)),
                'event_type'      => $request->input('event'),
                'signature_valid' => false,
                'payload'         => PayloadRedactor::redact($request->all()),
                'received_at'     => now(),
                'processing_error' => 'invalid_signature',
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $event = $adapter->parseWebhook($request->all());
        $this->service->handle($provider->value, $event);

        return response()->json(['ok' => true]);
    }
}
