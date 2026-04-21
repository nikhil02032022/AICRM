<?php

declare(strict_types=1);

namespace App\Console\Commands\CRM;

use App\Enums\CRM\Documents\DocumentReminderStatus;
use App\Enums\CRM\Documents\DocumentStatus;
use App\Jobs\CRM\Documents\SendDocumentReminderJob;
use App\Models\CRM\Documents\DocumentReminder;
use Illuminate\Console\Command;

// BRD: CRM-DM-005
class DispatchDocumentRemindersCommand extends Command
{
    protected $signature = 'crm:documents:dispatch-reminders';

    protected $description = 'Dispatch all due document reminder jobs.';

    public function handle(): int
    {
        $pendingStatuses = [
            DocumentStatus::NOT_SUBMITTED->value,
            DocumentStatus::SUBMITTED->value,
            DocumentStatus::UNDER_REVIEW->value,
            DocumentStatus::REJECTED->value,
        ];

        $count = 0;
        DocumentReminder::withoutGlobalScopes()
            ->where('status', DocumentReminderStatus::PENDING->value)
            ->where('scheduled_for', '<=', now())
            ->whereHas('document', function ($q) use ($pendingStatuses): void {
                $q->withoutGlobalScopes()->whereIn('status', $pendingStatuses);
            })
            ->orderBy('scheduled_for')
            ->chunkById(200, function ($reminders) use (&$count): void {
                foreach ($reminders as $reminder) {
                    SendDocumentReminderJob::dispatch($reminder->id);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} document reminders.");

        return self::SUCCESS;
    }
}
