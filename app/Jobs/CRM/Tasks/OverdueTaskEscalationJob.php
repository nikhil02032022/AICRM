<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Tasks;

use App\Models\CRM\Institution;
use App\Services\CRM\Tasks\TaskEscalationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// BRD: CRM-TF-004 — Hourly job: detect and flag overdue tasks, fire escalation notifications
final class OverdueTaskEscalationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public int $timeout = 120;

    public function __construct(
        public readonly ?int $institutionId = null,
    ) {
        $this->onQueue('crm-default');
    }

    public function handle(TaskEscalationService $service): void
    {
        $institutionIds = $this->institutionId !== null
            ? collect([$this->institutionId])
            : Institution::query()->where('is_active', true)->pluck('id');

        foreach ($institutionIds as $id) {
            $lock = Cache::lock("cron:overdue-task:{$id}", 3600);

            if (! $lock->get()) {
                continue;
            }

            try {
                $institution = Institution::withoutGlobalScopes()->find($id);
                if ($institution === null) {
                    continue;
                }

                $flagged = $service->detectAndFlagOverdue($institution);

                Log::info('Overdue tasks flagged and escalated', [
                    'institution_id' => (int) $id,
                    'tasks_flagged'  => $flagged,
                ]);
            } finally {
                $lock->release();
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('OverdueTaskEscalationJob failed', [
            'institution_id' => $this->institutionId,
            'error'          => $exception->getMessage(),
        ]);
    }
}
