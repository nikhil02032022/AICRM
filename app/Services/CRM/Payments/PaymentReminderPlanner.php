<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments;

use App\Enums\CRM\Payments\PaymentChannel;
use App\Enums\CRM\Payments\ReminderStatus;
use App\Models\CRM\Payments\PaymentReminder;
use App\Models\CRM\Payments\PaymentTransaction;
use Carbon\CarbonImmutable;

// BRD: CRM-FM-010 — Seed reminder rows according to configured cadence.
final class PaymentReminderPlanner
{
    /** @var array<int,PaymentChannel> */
    private array $channels = [PaymentChannel::EMAIL, PaymentChannel::WHATSAPP];

    public function plan(PaymentTransaction $transaction, CarbonImmutable $dueAt): void
    {
        $cadence = (array) config('crm_payments.reminders.cadence_days', [-3, -1, 1]);

        foreach ($cadence as $offset) {
            $scheduled = $dueAt->addDays((int) $offset);
            foreach ($this->channels as $channel) {
                PaymentReminder::firstOrCreate(
                    [
                        'payment_transaction_id' => $transaction->id,
                        'scheduled_for'          => $scheduled,
                        'channel'                => $channel->value,
                    ],
                    [
                        'institution_id' => $transaction->institution_id,
                        'due_at'         => $dueAt,
                        'status'         => ReminderStatus::PENDING->value,
                        'opted_out'      => false,
                    ],
                );
            }
        }
    }
}
