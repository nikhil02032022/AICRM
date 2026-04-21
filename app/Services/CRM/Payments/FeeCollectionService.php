<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use App\Enums\CRM\Payments\GatewayProvider;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Services\CRM\Payments\Gateways\PaymentGatewayManager;
use App\Services\CRM\Payments\Support\PayloadRedactor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

// BRD: CRM-FM-001, CRM-FM-002 — Initiate a payment for an application.
final class FeeCollectionService
{
    public function __construct(
        private readonly FeeStructureService $feeStructures,
        private readonly PaymentGatewayManager $gateways,
    ) {}

    public function initiate(
        Application $application,
        FeeType $feeType,
        ?GatewayProvider $gateway = null,
    ): PaymentTransaction {
        $gateway ??= GatewayProvider::from((string) config('crm_payments.default_gateway'));

        return DB::transaction(function () use ($application, $feeType, $gateway) {
            $existing = PaymentTransaction::query()
                ->where('application_uuid', $application->uuid)
                ->where('fee_type', $feeType->value)
                ->whereIn('status', [PaymentStatus::INITIATED->value, PaymentStatus::PENDING->value])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $feeStructure = $this->feeStructures->resolveActive(
                (int) $application->programme_id,
                $feeType,
            );

            if ($feeStructure === null) {
                throw new RuntimeException("No active fee structure for programme/{$feeType->value}.");
            }

            $txn = new PaymentTransaction([
                'institution_id'     => $application->institution_id,
                'campus_id'          => $application->campus_id,
                'application_uuid'   => $application->uuid,
                'lead_uuid'          => $application->lead_uuid,
                'fee_structure_id'   => $feeStructure->id,
                'fee_type'           => $feeType,
                'gateway'            => $gateway,
                'amount'             => $feeStructure->amount,
                'currency'           => $feeStructure->currency,
                'status'             => PaymentStatus::INITIATED,
                'idempotency_key'    => 'fc_'.Str::random(24),
                'attempted_at'       => now(),
                'created_by'         => Auth::id(),
            ]);
            $txn->save();

            $order = $this->gateways->driver($gateway)->createOrder($txn);

            $txn->gateway_order_id = $order->gatewayOrderId;
            $txn->raw_request = PayloadRedactor::redact($order->checkoutPayload);
            $txn->raw_response = PayloadRedactor::redact($order->rawResponse);
            $txn->status = PaymentStatus::PENDING;
            $txn->save();

            return $txn;
        });
    }
}
