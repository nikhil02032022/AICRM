<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\AadhaarEkycLog;
use App\Services\CRM\Integration\AadhaarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-DM-007 — Async Aadhaar OTP send via API Setu
final class ProcessAadhaarKycJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;
    public int $timeout = 30;

    public function __construct(
        private readonly int $logId
    ) {}

    public function handle(AadhaarService $service): void
    {
        $log = AadhaarEkycLog::withoutGlobalScopes()->findOrFail($this->logId);

        // BRD: CRM-DM-007 — Integration stub: replace with real API Setu Aadhaar OTP request
        // Actual implementation would call API Setu to send OTP to the Aadhaar-linked mobile.
        // Aadhaar number is passed as a secured, short-lived token — NEVER stored in DB.
        $service->repository ?? null; // service injected only — no model PII stored

        // Update log to OTP_SENT status
        $service->findByUuid($log->uuid)?->update(['status' => 'otp_sent', 'otp_reference' => 'stub_' . $log->uuid]);
    }

    public function failed(\Throwable $exception): void
    {
        $log = AadhaarEkycLog::withoutGlobalScopes()->find($this->logId);

        if ($log !== null) {
            app(AadhaarService::class)->markFailed($log, 'Job failed after max retries');
        }
    }
}
