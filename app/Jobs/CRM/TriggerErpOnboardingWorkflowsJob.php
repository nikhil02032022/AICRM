<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Services\CRM\Erp\ErpOnboardingWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AP-018 — Async job to trigger ERP onboarding sub-workflows after conversion success
final class TriggerErpOnboardingWorkflowsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;

    public function __construct(
        public readonly string $conversionLogUuid,
        public readonly int $institutionId,
        public readonly string $erpStudentId,
    ) {}

    public function handle(ErpOnboardingWorkflowService $onboardingService): void
    {
        $log = ApplicationConversionLog::withoutGlobalScopes()
            ->where('uuid', $this->conversionLogUuid)
            ->where('institution_id', $this->institutionId)
            ->first();

        if ($log === null) {
            Log::warning('TriggerErpOnboardingWorkflowsJob: conversion log not found.', [
                'uuid' => $this->conversionLogUuid,
            ]);
            return;
        }

        $application = Application::withoutGlobalScopes()
            ->where('uuid', $log->application_uuid)
            ->where('institution_id', $this->institutionId)
            ->first();

        if ($application === null) {
            Log::warning('TriggerErpOnboardingWorkflowsJob: application not found.', [
                'application_uuid' => $log->application_uuid,
            ]);
            return;
        }

        $results = $onboardingService->triggerAll($this->erpStudentId, $application);

        ApplicationConversionLog::withoutGlobalScopes()
            ->where('uuid', $log->uuid)
            ->update([
                'onboarding_triggered_at' => now(),
                'onboarding_status'       => json_encode($results),
            ]);
    }
}
