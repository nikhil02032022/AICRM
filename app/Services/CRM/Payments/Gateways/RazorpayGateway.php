<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments\Gateways;

use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\CRM\Payments\RefundRequest;
use App\Services\CRM\Payments\Gateways\DTO\GatewayOrder;
use App\Services\CRM\Payments\Gateways\DTO\GatewayRefund;
use App\Services\CRM\Payments\Gateways\DTO\NormalizedEvent;
use Illuminate\Support\Facades\Http;
use RuntimeException;

// BRD: CRM-FM-003 — Razorpay adapter (primary gateway)
final class RazorpayGateway implements PaymentGatewayInterface
{
    /**
     * @param array<string,mixed> $config
     */
    public function __construct(private readonly array $config) {}

    public function provider(): string
    {
        return 'razorpay';
    }

    public function createOrder(PaymentTransaction $transaction): GatewayOrder
    {
        $payload = [
            'amount'   => (int) round((float) $transaction->amount * 100),
            'currency' => $transaction->currency,
            'receipt'  => $transaction->idempotency_key,
            'notes'    => [
                'transaction_uuid' => $transaction->uuid,
                'application_uuid' => $transaction->application_uuid,
                'fee_type'         => $transaction->fee_type?->value,
            ],
        ];

        $response = Http::withBasicAuth(
            (string) $this->config['key_id'],
            (string) $this->config['key_secret'],
        )->acceptJson()
            ->post(rtrim((string) $this->config['base_url'], '/').'/orders', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Razorpay createOrder failed: '.$response->status());
        }

        $data = $response->json();
        $orderId = (string) ($data['id'] ?? '');

        return new GatewayOrder(
            gatewayOrderId: $orderId,
            checkoutUrl: route(config('crm_payments.link.route_name'), ['token' => $transaction->idempotency_key]),
            checkoutPayload: [
                'key'      => $this->config['key_id'],
                'order_id' => $orderId,
                'amount'   => $payload['amount'],
                'currency' => $payload['currency'],
            ],
            rawResponse: $data,
        );
    }

    public function verifySignature(string $rawPayload, array $headers): bool
    {
        $secret = (string) ($this->config['webhook_secret'] ?? '');
        $signature = $headers['x-razorpay-signature'] ?? $headers['X-Razorpay-Signature'] ?? '';

        if ($secret === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawPayload, $secret);

        return hash_equals($expected, (string) $signature);
    }

    public function parseWebhook(array $payload): NormalizedEvent
    {
        $event = (string) ($payload['event'] ?? '');
        $entity = $payload['payload']['payment']['entity'] ?? [];

        $status = match ($event) {
            'payment.captured', 'order.paid' => PaymentStatus::SUCCESS,
            'payment.failed' => PaymentStatus::FAILED,
            'refund.processed' => PaymentStatus::REFUNDED,
            default => PaymentStatus::PENDING,
        };

        $amount = isset($entity['amount']) ? ((float) $entity['amount']) / 100 : null;

        return new NormalizedEvent(
            eventId: (string) ($payload['id'] ?? ($entity['id'] ?? bin2hex(random_bytes(8)))),
            eventType: $event,
            gatewayOrderId: $entity['order_id'] ?? null,
            gatewayPaymentId: $entity['id'] ?? null,
            status: $status,
            amount: $amount,
            currency: $entity['currency'] ?? null,
            failureReason: $entity['error_description'] ?? null,
            raw: $payload,
        );
    }

    public function fetchStatus(string $gatewayPaymentId): NormalizedEvent
    {
        $response = Http::withBasicAuth(
            (string) $this->config['key_id'],
            (string) $this->config['key_secret'],
        )->acceptJson()
            ->get(rtrim((string) $this->config['base_url'], '/')."/payments/{$gatewayPaymentId}");

        if (! $response->successful()) {
            throw new RuntimeException('Razorpay fetchStatus failed: '.$response->status());
        }

        $entity = $response->json();
        $status = match ($entity['status'] ?? '') {
            'captured', 'authorized' => PaymentStatus::SUCCESS,
            'failed' => PaymentStatus::FAILED,
            'refunded' => PaymentStatus::REFUNDED,
            default => PaymentStatus::PENDING,
        };

        return new NormalizedEvent(
            eventId: (string) $entity['id'],
            eventType: 'fetch.status',
            gatewayOrderId: $entity['order_id'] ?? null,
            gatewayPaymentId: $entity['id'] ?? null,
            status: $status,
            amount: isset($entity['amount']) ? ((float) $entity['amount']) / 100 : null,
            currency: $entity['currency'] ?? null,
            failureReason: $entity['error_description'] ?? null,
            raw: $entity,
        );
    }

    public function initiateRefund(RefundRequest $refund): GatewayRefund
    {
        $paymentId = (string) ($refund->transaction?->gateway_payment_id ?? '');
        if ($paymentId === '') {
            throw new RuntimeException('Refund requires a captured gateway_payment_id.');
        }

        $response = Http::withBasicAuth(
            (string) $this->config['key_id'],
            (string) $this->config['key_secret'],
        )->acceptJson()
            ->post(rtrim((string) $this->config['base_url'], '/')."/payments/{$paymentId}/refund", [
                'amount' => (int) round((float) $refund->amount * 100),
                'notes'  => ['refund_uuid' => $refund->uuid],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Razorpay refund failed: '.$response->status());
        }

        $data = $response->json();

        return new GatewayRefund(
            gatewayRefundId: (string) ($data['id'] ?? ''),
            status: (string) ($data['status'] ?? 'processed'),
            raw: $data,
        );
    }
}
