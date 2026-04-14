<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\LmsEnrolmentLog;
use App\Services\CRM\Integration\LmsEnrolmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-EI-010 — Async LMS enrolment trigger for CamPLUS / Moodle
final class TriggerLmsEnrolmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 120;
    public int $timeout = 60;

    public function __construct(
        private readonly int $logId
    ) {}

    public function handle(LmsEnrolmentService $service): void
    {
        $log = LmsEnrolmentLog::withoutGlobalScopes()->findOrFail($this->logId);

        $service->incrementAttempts($log);

        // BRD: CRM-EI-010 — Integration stub: replace with real CamPLUS/Moodle API enrolment call
        // Actual implementation would POST to the LMS API using the course ID and student details.
        $stubLmsUserId = ($log->lms_provider ?? 'lms') . '-user-' . $log->erp_student_id;

        $service->markEnrolled($log, $stubLmsUserId);
    }

    public function failed(\Throwable $exception): void
    {
        $log = LmsEnrolmentLog::withoutGlobalScopes()->find($this->logId);

        if ($log !== null) {
            app(LmsEnrolmentService::class)->markFailed($log, 'Job failed after max retries');
        }
    }
}
