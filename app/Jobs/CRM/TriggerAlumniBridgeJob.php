<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\AlumniBridgeLog;
use App\Services\CRM\Integration\AlumniBridgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-EI-008 — Async A2A Alumni module bridge trigger
final class TriggerAlumniBridgeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 120;
    public int $timeout = 60;

    public function __construct(
        private readonly int $logId
    ) {}

    public function handle(AlumniBridgeService $service): void
    {
        $log = AlumniBridgeLog::withoutGlobalScopes()->findOrFail($this->logId);

        // BRD: CRM-EI-008 — Integration stub: replace with real A2A ERP Alumni API call
        // Actual implementation would POST to the A2A Alumni module API with the student record.
        // The ERP returns an alumni_id which is stored in erp_alumni_id.
        $stubAlumniId = 'ALM-' . strtoupper(substr((string) $log->erp_student_id, 0, 8));

        $service->markSuccess($log, $stubAlumniId);
    }

    public function failed(\Throwable $exception): void
    {
        $log = AlumniBridgeLog::withoutGlobalScopes()->find($this->logId);

        if ($log !== null) {
            app(AlumniBridgeService::class)->markFailed($log, 'Job failed after max retries');
        }
    }
}
