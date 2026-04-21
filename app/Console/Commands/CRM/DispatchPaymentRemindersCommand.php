<?php

declare(strict_types=1);

namespace App\Console\Commands\CRM;

use App\Enums\CRM\Payments\PaymentStatus;
use App\Enums\CRM\Payments\ReminderStatus;
use App\Jobs\CRM\Payments\SendPaymentReminderJob;
use App\Models\CRM\Payments\PaymentReminder;
use Illuminate\Console\Command;

// BRD: CRM-FM-010 — Scheduled command to enqueue due reminders.
class DispatchPaymentRemindersCommand extends Command
{
    protected $signature = 'crm:payments:dispatch-reminders';

    protected $description = 'Dispatch all due payment reminder jobs.';

    public function handle(): int
    {
        $count = 0;
        PaymentReminder::withoutGlobalScopes()
            ->where('status', ReminderStatus::PENDING->value)
            ->where('scheduled_for', '<=', now())
            ->whereHas('transaction', function ($q) {
                $q->withoutGlobalScopes()
                  ->whereIn('status', [PaymentStatus::INITIATED->value, PaymentStatus::PENDING->value]);
            })
            ->orderBy('scheduled_for')
            ->chunkById(200, function ($reminders) use (&$count): void {
                foreach ($reminders as $reminder) {
                    SendPaymentReminderJob::dispatch($reminder->id);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} reminders.");
        return self::SUCCESS;
    }
}
