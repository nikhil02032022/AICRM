<?php

declare(strict_types=1);

// BRD: CRM-FM-011 — Refund approval chain + gateway dispatch

use App\Enums\CRM\Payments\PaymentStatus;
use App\Enums\CRM\Payments\RefundStatus;
use App\Jobs\CRM\Payments\ProcessGatewayRefundJob;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\User;
use App\Services\CRM\Payments\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('walks pending → manager → finance approval and queues gateway refund job', function () {
    Bus::fake();
    $this->actingAs(User::factory()->create());

    $txn = PaymentTransaction::factory()->create([
        'status' => PaymentStatus::SUCCESS->value,
        'amount' => 1500,
        'gateway_payment_id' => 'pay_ok',
    ]);

    $svc = app(RefundService::class);

    $refund = $svc->request($txn, 'duplicate', 1500);
    expect($refund->status)->toBe(RefundStatus::PENDING);

    $refund = $svc->managerApprove($refund);
    expect($refund->status)->toBe(RefundStatus::MANAGER_APPROVED);

    $refund = $svc->financeApprove($refund);
    expect($refund->status)->toBe(RefundStatus::APPROVED);

    Bus::assertDispatched(ProcessGatewayRefundJob::class);
});

it('rejects refund request for a non-successful transaction', function () {
    $txn = PaymentTransaction::factory()->create([
        'status' => PaymentStatus::PENDING->value,
        'amount' => 1500,
    ]);

    app(RefundService::class)->request($txn, 'no good', 1500);
})->throws(RuntimeException::class);
