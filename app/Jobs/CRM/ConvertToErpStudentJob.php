<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Enums\CRM\ApplicationStatus;
use App\Events\CRM\ErpConversionFailedEvent;
use App\Events\CRM\ErpConversionSucceededEvent;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\CRM\Lead;
use App\Services\CRM\Application\ApplicationPipelineService;
use App\Services\CRM\Erp\ErpApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AP-016 — Async ERP Student Master registration and ENROLLED transition
final class ConvertToErpStudentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;   // ErpApiClient has internal 3× retries; explicit retry via ErpConversionService::retry()
    public int $timeout = 45;

    public function __construct(
        public readonly string $conversionLogUuid,
        public readonly int $institutionId,
    ) {}

    public function handle(ApplicationPipelineService $pipelineService): void
    {
        // BRD: CRM-MT-001 — Bypass InstitutionScope inside job; re-scope manually
        $log = ApplicationConversionLog::withoutGlobalScopes()
            ->where('uuid', $this->conversionLogUuid)
            ->where('institution_id', $this->institutionId)
            ->first();

        if ($log === null) {
            Log::warning('ConvertToErpStudentJob: log not found.', ['uuid' => $this->conversionLogUuid]);
            return;
        }

        $application = Application::withoutGlobalScopes()
            ->where('uuid', $log->application_uuid)
            ->where('institution_id', $this->institutionId)
            ->first();

        if ($application === null) {
            $this->recordFailure($log, 'Application not found for conversion.');
            return;
        }

        $client = ErpApiClient::forInstitution($this->institutionId);

        // DPDP: payload was built at dispatch time — use stored conversion_payload directly
        $erpStudentId = $client->registerStudent($log->conversion_payload ?? []);

        if ($erpStudentId !== null && $erpStudentId !== '') {
            $this->recordSuccess($log, $application, $erpStudentId, $pipelineService);
        } else {
            $this->recordFailure($log, 'ERP API returned no student ID — API may be unavailable or misconfigured.');
        }
    }

    private function recordSuccess(
        ApplicationConversionLog $log,
        Application $application,
        string $erpStudentId,
        ApplicationPipelineService $pipelineService,
    ): void {
        ApplicationConversionLog::withoutGlobalScopes()
            ->where('id', $log->id)
            ->update([
                'status'         => 'success',
                'erp_student_id' => $erpStudentId,
                'completed_at'   => now(),
                'error_message'  => null,
            ]);

        // Transition application to ENROLLED
        try {
            $pipelineService->transition(
                $application,
                ApplicationStatus::ENROLLED,
                $log->converted_by_user_id,
                'ERP Student Master conversion completed.',
            );
        } catch (\Throwable $e) {
            Log::warning('ConvertToErpStudentJob: could not transition to ENROLLED — already enrolled or invalid state.', [
                'application_uuid' => $application->uuid,
                'error' => $e->getMessage(),
            ]);
        }

        // Update lead with ERP student ID
        if ($log->lead_uuid !== null) {
            Lead::withoutGlobalScopes()
                ->where('uuid', $log->lead_uuid)
                ->where('institution_id', $this->institutionId)
                ->update(['erp_student_uuid' => $erpStudentId]);
        }

        $application->refresh();
        ErpConversionSucceededEvent::dispatch($application, $erpStudentId);
    }

    private function recordFailure(ApplicationConversionLog $log, string $errorMessage): void
    {
        $retryCount = (int) $log->retry_count + 1;
        $nextRetryAt = match (true) {
            $retryCount === 1 => now()->addMinutes(5),
            $retryCount === 2 => now()->addMinutes(30),
            default           => now()->addHours(2),
        };

        ApplicationConversionLog::withoutGlobalScopes()
            ->where('id', $log->id)
            ->update([
                'status'        => 'failed',
                'error_message' => $errorMessage,
                'retry_count'   => $retryCount,
                'next_retry_at' => $retryCount < 3 ? $nextRetryAt : null,
            ]);

        $log->refresh();
        ErpConversionFailedEvent::dispatch($log, $errorMessage);
    }
}
