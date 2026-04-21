<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Payments;

use App\Enums\CRM\Payments\PaymentStatus;
use App\Enums\CRM\Payments\RefundStatus;
use App\Models\CRM\Payments\RefundRequest;
use App\Services\CRM\Payments\Gateways\PaymentGatewayManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

// BRD: CRM-FM-011 — Execute approved refund against gateway.
class ProcessGatewayRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $refundId) {}

    public function handle(PaymentGatewayManager $gateways): void
    {
        $refund = RefundRequest::withoutGlobalScopes()->find($this->refundId);
        if ($refund === null || $refund->status !== RefundStatus::APPROVED) {
            return;
        }

        $txn = $refund->transaction()->withoutGlobalScopes()->first();
        if ($txn === null) {
            return;
        }

        try {
            $result = $gateways->driver($txn->gateway)->initiateRefund($refund);

            $refund->gateway_refund_id = $result->gatewayRefundId;
            $refund->status = RefundStatus::PROCESSED;
            $refund->processed_at = now();
            $refund->save();

            $txn->status = PaymentStatus::REFUNDED;
            $txn->save();
        } catch (Throwable $e) {
            $refund->status = RefundStatus::FAILED;
            $refund->failure_reason = mb_substr($e->getMessage(), 0, 250);
            $refund->save();
            throw $e;
        }
    }
}
