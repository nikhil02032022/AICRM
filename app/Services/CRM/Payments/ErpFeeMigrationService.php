<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments;

use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\Payments\PaymentTransaction;

// BRD: CRM-FM-013 — Build the ERP fee ledger payload for an enrolled application.
final class ErpFeeMigrationService
{
    /**
     * @return array<string,mixed>
     */
    public function buildPayload(Application $application): array
    {
        $transactions = PaymentTransaction::withoutGlobalScopes()
            ->where('application_uuid', $application->uuid)
            ->whereIn('status', [
                PaymentStatus::SUCCESS->value,
                PaymentStatus::REFUNDED->value,
            ])
            ->orderBy('confirmed_at')
            ->get();

        $ledger = $transactions->map(fn (PaymentTransaction $t) => [
            'crm_transaction_uuid' => $t->uuid,
            'fee_type'             => $t->fee_type?->value,
            'amount'               => (float) $t->amount,
            'currency'             => $t->currency,
            'status'               => $t->status->value,
            'gateway'              => $t->gateway?->value,
            'gateway_payment_id'   => $t->gateway_payment_id,
            'confirmed_at'         => optional($t->confirmed_at)->toIso8601String(),
        ])->all();

        return [
            'crm_application_uuid' => $application->uuid,
            'lead_uuid'            => $application->lead_uuid,
            'programme_id'         => $application->programme_id,
            'transactions'         => $ledger,
            'total_collected'      => (float) $transactions->where('status', PaymentStatus::SUCCESS)->sum('amount'),
            'total_refunded'       => (float) $transactions->where('status', PaymentStatus::REFUNDED)->sum('amount'),
        ];
    }
}
