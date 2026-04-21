<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Tasks;

use App\Models\CRM\Institution;
use App\Services\CRM\Tasks\TaskAutoRuleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// BRD: CRM-TF-002 — Daily job: create follow-up tasks for inactive leads per institution rules
final class AutoCreateFollowUpTaskJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 300;

    public function __construct(
        public readonly ?int $institutionId = null,
    ) {
        $this->onQueue('crm-default');
    }

    public function uniqueId(): string
    {
        return 'auto-task-create:'.($this->institutionId ?? 'all').':'.now()->toDateString();
    }

    public function handle(TaskAutoRuleService $service): void
    {
        $institutionIds = $this->institutionId !== null
            ? collect([$this->institutionId])
            : Institution::query()->where('is_active', true)->pluck('id');

        foreach ($institutionIds as $id) {
            $lock = Cache::lock("cron:auto-task:{$id}", 3600);

            if (! $lock->get()) {
                continue;
            }

            try {
                $institution = Institution::withoutGlobalScopes()->find($id);
                if ($institution === null) {
                    continue;
                }

                $count = $service->evaluateRulesForInstitution($institution);

                Log::info('Auto follow-up tasks created', [
                    'institution_id' => (int) $id,
                    'tasks_created'  => $count,
                ]);
            } finally {
                $lock->release();
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AutoCreateFollowUpTaskJob failed', [
            'institution_id' => $this->institutionId,
            'error'          => $exception->getMessage(),
        ]);
    }
}
