<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Payments;

use App\Enums\CRM\Payments\PaymentStatus;
use App\Enums\CRM\Payments\RefundStatus;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\CRM\Payments\RefundRequest;

// BRD: CRM-FM-012 — Aggregations for finance dashboards.
final class EloquentPaymentReportRepository implements PaymentReportRepositoryInterface
{
    /**
     * @param array<string,mixed> $filters
     */
    public function summary(array $filters): array
    {
        $base = PaymentTransaction::query()->when($filters['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v));

        $collected = (clone $base)->where('status', PaymentStatus::SUCCESS->value)->sum('amount');
        $pending   = (clone $base)->whereIn('status', [PaymentStatus::INITIATED->value, PaymentStatus::PENDING->value])->sum('amount');
        $refunded  = (clone $base)->where('status', PaymentStatus::REFUNDED->value)->sum('amount');

        $refundsRequested = RefundRequest::query()
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v))
            ->whereIn('status', [
                RefundStatus::PENDING->value,
                RefundStatus::MANAGER_APPROVED->value,
                RefundStatus::APPROVED->value,
            ])
            ->sum('amount');

        return [
            'collected'         => (float) $collected,
            'pending'           => (float) $pending,
            'refunded'          => (float) $refunded,
            'refunds_requested' => (float) $refundsRequested,
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function programmeBreakdown(array $filters): array
    {
        return PaymentTransaction::query()
            ->selectRaw('a.programme_id as programme_id, payment_transactions.status as status, SUM(payment_transactions.amount) as total')
            ->join('applications as a', 'a.uuid', '=', 'payment_transactions.application_uuid')
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->where('payment_transactions.created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('payment_transactions.created_at', '<=', $v))
            ->groupBy('a.programme_id', 'payment_transactions.status')
            ->get()
            ->map(fn ($row) => [
                'programme_id' => (int) $row->programme_id,
                'status'       => (string) $row->status,
                'total'        => (float) $row->total,
            ])->all();
    }
}
