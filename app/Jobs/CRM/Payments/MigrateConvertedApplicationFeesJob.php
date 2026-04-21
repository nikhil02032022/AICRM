<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Payments;

use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Services\CRM\Erp\ErpApiClient;
use App\Services\CRM\Payments\ErpFeeMigrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

// BRD: CRM-FM-013 — Push CRM fee ledger into ERP after conversion (idempotent).
class MigrateConvertedApplicationFeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $conversionLogUuid,
        public readonly int $institutionId,
        public readonly string $erpStudentId,
    ) {}

    public function handle(ErpFeeMigrationService $service): void
    {
        $log = ApplicationConversionLog::withoutGlobalScopes()
            ->where('uuid', $this->conversionLogUuid)
            ->first();

        if ($log === null || $log->fee_migration_status === 'success') {
            return;
        }

        $application = Application::withoutGlobalScopes()
            ->where('uuid', $log->application_uuid)
            ->first();

        if ($application === null) {
            return;
        }

        $log->fee_migration_status = 'in_progress';
        $log->fee_migration_attempted_at = now();
        $log->save();

        try {
            $payload = $service->buildPayload($application);
            $client  = ErpApiClient::forInstitution($this->institutionId);
            $ledgerId = $client->pushFeeLedger($this->erpStudentId, $payload);

            if ($ledgerId === null) {
                $log->fee_migration_status = 'failed';
                $log->fee_migration_error = 'ERP push returned null.';
            } else {
                $log->fee_migration_status = 'success';
                $log->fee_migration_completed_at = now();
                $log->fee_migration_error = null;
            }
            $log->save();
        } catch (Throwable $e) {
            $log->fee_migration_status = 'failed';
            $log->fee_migration_error = mb_substr($e->getMessage(), 0, 250);
            $log->save();
            throw $e;
        }
    }
}
