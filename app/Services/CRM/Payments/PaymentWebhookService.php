<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments;

use App\Enums\CRM\Payments\PaymentStatus;
use App\Events\CRM\Payments\PaymentConfirmed;
use App\Events\CRM\Payments\PaymentFailed;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\CRM\Payments\PaymentWebhookEvent;
use App\Services\CRM\Payments\Gateways\DTO\NormalizedEvent;
use App\Services\CRM\Payments\Support\PayloadRedactor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

// BRD: CRM-FM-005 — Idempotent processing of normalized gateway events.
final class PaymentWebhookService
{
    public function handle(string $gateway, NormalizedEvent $event): PaymentWebhookEvent
    {
        $record = PaymentWebhookEvent::firstOrNew(
            ['gateway' => $gateway, 'event_id' => $event->eventId],
            [
                'event_type'      => $event->eventType,
                'signature_valid' => true,
                'payload'         => PayloadRedactor::redact($event->raw),
                'received_at'     => now(),
            ],
        );

        if ($record->exists && $record->processed_at !== null) {
            return $record;
        }

        if (! $record->exists) {
            $record->save();
        }

        try {
            DB::transaction(function () use ($event): void {
                $txn = PaymentTransaction::withoutGlobalScopes()
                    ->when($event->gatewayPaymentId, fn ($q) => $q->orWhere('gateway_payment_id', $event->gatewayPaymentId))
                    ->when($event->gatewayOrderId, fn ($q) => $q->orWhere('gateway_order_id', $event->gatewayOrderId))
                    ->lockForUpdate()
                    ->first();

                if ($txn === null) {
                    return;
                }

                $txn->status = $event->status;
                if ($event->gatewayPaymentId) {
                    $txn->gateway_payment_id = $event->gatewayPaymentId;
                }
                if ($event->status === PaymentStatus::SUCCESS) {
                    $txn->confirmed_at = now();
                }
                if ($event->status === PaymentStatus::FAILED) {
                    $txn->failure_reason = $event->failureReason;
                }
                $txn->raw_response = PayloadRedactor::redact(array_merge((array) $txn->raw_response, $event->raw));
                $txn->save();

                if ($event->status === PaymentStatus::SUCCESS) {
                    PaymentConfirmed::dispatch($txn);
                } elseif ($event->status === PaymentStatus::FAILED) {
                    PaymentFailed::dispatch($txn, $event->failureReason);
                }
            });

            $record->processed_at = now();
            $record->save();
        } catch (Throwable $e) {
            $record->processing_error = mb_substr($e->getMessage(), 0, 250);
            $record->save();
            Log::error('payments.webhook.processing_failed', [
                'gateway'  => $gateway,
                'event_id' => $event->eventId,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }

        return $record;
    }
}
