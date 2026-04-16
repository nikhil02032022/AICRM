<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Events\CRM\Application\LeadConvertedToStudentEvent;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\CRM\LeadStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * BRD: CRM-AP-016, CRM-AP-017 — Async job to call ERP API and convert applicant to Student
 * Must be idempotent: use conversion_log.erp_student_id as idempotency key
 * Retry up to 3 times with exponential backoff
 */
final class ConvertToStudentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1 min, 2 min, 4 min

    public function __construct(
        private readonly ApplicationConversionLog $conversionLog,
    ) {}

    public function handle(): void
    {
        try {
            // Idempotency: skip if already successfully converted
            if ($this->conversionLog->erp_student_id) {
                \Log::info('ConvertToStudentJob: skipping (already converted)', [
                    'conversion_log_uuid' => $this->conversionLog->uuid,
                    'erp_student_id' => $this->conversionLog->erp_student_id,
                ]);
                return;
            }

            // Call ERP integration to create Student Master record
            $erpResponse = $this->callErpApi($this->conversionLog);

            // Extract student ID from ERP response
            $erpStudentId = $erpResponse['student_id'] ?? null;
            if (! $erpStudentId) {
                throw new \RuntimeException('ERP API did not return student_id in response');
            }

            // Update conversion log with success status
            $this->conversionLog->update([
                'status' => 'success',
                'erp_student_id' => $erpStudentId,
                'erp_response' => $erpResponse,
                'completed_at' => now(),
            ]);

            // Update lead status to ENROLLED
            $this->conversionLog->lead->update([
                'status' => \App\Enums\CRM\LeadStatus::ENROLLED,
            ]);

            // Fire event for listeners (audit logging, notifications, etc.)
            LeadConvertedToStudentEvent::dispatch(
                $this->conversionLog->application,
                $erpStudentId,
                $this->conversionLog->converted_by_user_id,
            );

        } catch (\Exception $e) {
            // Mark as failed and schedule retry
            $retryAt = now()->addMinutes(2 ** $this->attempts());

            $this->conversionLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => $this->conversionLog->retry_count + 1,
                'next_retry_at' => $retryAt,
            ]);

            \Log::error('ConvertToStudentJob failed', [
                'conversion_log_uuid' => $this->conversionLog->uuid,
                'attempt' => $this->attempts(),
                'next_retry_at' => $retryAt,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger Laravel's retry mechanism
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * Call ERP API to create Student Master record.
     * TODO: Replace with real ERP integration contract call
     *
     * @return array{'student_id': string, ...}
     */
    private function callErpApi(ApplicationConversionLog $conversionLog): array
    {
        // Stub implementation: returns mocked response
        // Real implementation: call A2A ERP integration gateway

        $payload = $conversionLog->conversion_payload;

        return [
            'success' => true,
            'student_id' => 'ERP-' . \Illuminate\Support\Str::random(10),
            'application_uuid' => $payload['application_uuid'],
            'programme_uuid' => $payload['programme_uuid'],
            'created_at' => now()->toIso8601String(),
        ];
    }
}
