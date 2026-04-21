<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments;

use App\Enums\CRM\Payments\PaymentStatus;
use App\Enums\CRM\Payments\RefundStatus;
use App\Jobs\CRM\Payments\ProcessGatewayRefundJob;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\CRM\Payments\RefundRequest;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

// BRD: CRM-FM-011 — Refund request workflow service.
final class RefundService
{
    public function request(PaymentTransaction $transaction, string $reason, float $amount): RefundRequest
    {
        if ($transaction->status !== PaymentStatus::SUCCESS) {
            throw new RuntimeException('Refund allowed only on successful transactions.');
        }

        if ($amount <= 0 || $amount > (float) $transaction->amount) {
            throw new RuntimeException('Refund amount is invalid.');
        }

        return RefundRequest::create([
            'institution_id'         => $transaction->institution_id,
            'payment_transaction_id' => $transaction->id,
            'requested_by'           => Auth::id(),
            'reason'                 => $reason,
            'amount'                 => $amount,
            'status'                 => RefundStatus::PENDING->value,
            'approver_chain'         => [['by' => Auth::id(), 'role' => 'counsellor', 'at' => now()->toIso8601String()]],
        ]);
    }

    public function managerApprove(RefundRequest $refund): RefundRequest
    {
        if ($refund->status !== RefundStatus::PENDING) {
            throw new RuntimeException('Only pending refunds can be approved by manager.');
        }

        $refund->status = RefundStatus::MANAGER_APPROVED;
        $refund->approver_chain = array_merge((array) $refund->approver_chain, [
            ['by' => Auth::id(), 'role' => 'manager', 'at' => now()->toIso8601String()],
        ]);
        $refund->save();

        return $refund;
    }

    public function financeApprove(RefundRequest $refund): RefundRequest
    {
        if ($refund->status !== RefundStatus::MANAGER_APPROVED) {
            throw new RuntimeException('Refund must be manager-approved first.');
        }

        $refund->status = RefundStatus::APPROVED;
        $refund->approver_chain = array_merge((array) $refund->approver_chain, [
            ['by' => Auth::id(), 'role' => 'finance', 'at' => now()->toIso8601String()],
        ]);
        $refund->save();

        ProcessGatewayRefundJob::dispatch($refund->id);

        return $refund;
    }

    public function reject(RefundRequest $refund, string $reason): RefundRequest
    {
        $refund->status = RefundStatus::REJECTED;
        $refund->failure_reason = $reason;
        $refund->approver_chain = array_merge((array) $refund->approver_chain, [
            ['by' => Auth::id(), 'role' => 'rejected', 'at' => now()->toIso8601String(), 'reason' => $reason],
        ]);
        $refund->save();

        return $refund;
    }
}
