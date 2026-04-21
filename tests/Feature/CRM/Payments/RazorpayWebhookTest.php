<?php

declare(strict_types=1);

// BRD: CRM-FM-005 — Razorpay webhook signature verification + idempotent processing

use App\Enums\CRM\Payments\PaymentStatus;
use App\Events\CRM\Payments\PaymentConfirmed;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\CRM\Payments\PaymentWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('crm_payments.gateways.razorpay', [
        'driver' => 'razorpay',
        'key_id' => 'k', 'key_secret' => 's',
        'webhook_secret' => 'whsec',
        'base_url' => 'https://api.razorpay.test/v1',
    ]);
});

function razorpayPayload(string $orderId, string $paymentId, string $event = 'payment.captured'): array
{
    return [
        'id'    => 'evt_'.bin2hex(random_bytes(6)),
        'event' => $event,
        'payload' => [
            'payment' => [
                'entity' => [
                    'id'       => $paymentId,
                    'order_id' => $orderId,
                    'amount'   => 150000,
                    'currency' => 'INR',
                    'status'   => 'captured',
                ],
            ],
        ],
    ];
}

it('rejects an invalid signature', function () {
    $payload = razorpayPayload('order_x', 'pay_x');
    $response = $this->postJson('/api/v1/crm/payments/webhooks/razorpay', $payload, [
        'X-Razorpay-Signature' => 'wrong',
    ]);

    $response->assertStatus(401);
    expect(PaymentWebhookEvent::where('signature_valid', false)->count())->toBe(1);
});

it('processes a valid webhook and is idempotent on replay', function () {
    Event::fake([PaymentConfirmed::class]);

    $txn = PaymentTransaction::factory()->create([
        'gateway_order_id' => 'order_z',
        'status'           => PaymentStatus::PENDING->value,
    ]);

    $payload = razorpayPayload('order_z', 'pay_z');
    $raw     = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $sig     = hash_hmac('sha256', $raw, 'whsec');

    $headers = ['X-Razorpay-Signature' => $sig, 'Content-Type' => 'application/json'];

    $r1 = $this->call('POST', '/api/v1/crm/payments/webhooks/razorpay', [], [], [], [
        'CONTENT_TYPE'             => 'application/json',
        'HTTP_X-Razorpay-Signature' => $sig,
    ], $raw);
    $r1->assertOk();

    $r2 = $this->call('POST', '/api/v1/crm/payments/webhooks/razorpay', [], [], [], [
        'CONTENT_TYPE'             => 'application/json',
        'HTTP_X-Razorpay-Signature' => $sig,
    ], $raw);
    $r2->assertOk();

    $txn->refresh();
    expect($txn->status)->toBe(PaymentStatus::SUCCESS)
        ->and($txn->gateway_payment_id)->toBe('pay_z')
        ->and(PaymentWebhookEvent::where('event_id', $payload['id'])->count())->toBe(1);

    Event::assertDispatched(PaymentConfirmed::class, 1);
});
