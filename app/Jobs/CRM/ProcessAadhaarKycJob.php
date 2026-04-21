<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Enums\CRM\AadhaarKycStatus;
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

        // Idempotency: skip if already past INITIATED (job replayed after success/failure)
        if ($log->status !== AadhaarKycStatus::INITIATED) {
            return;
        }

        // BRD: CRM-DM-007 — Stub: replace with real API Setu Aadhaar OTP request.
        // POST to API Setu /aadhaar/otp — pass Aadhaar number as a short-lived secure token only.
        // Store only the returned transaction_id and otp_reference — NEVER store the Aadhaar number.
        $service->markOtpSent(
            $log,
            otpReference: 'stub_otp_ref_' . $log->uuid,
            transactionId: 'stub_txn_' . $log->uuid,
        );
    }

    public function failed(\Throwable $exception): void
    {
        $log = AadhaarEkycLog::withoutGlobalScopes()->find($this->logId);

        if ($log !== null) {
            app(AadhaarService::class)->markFailed($log, 'Job failed after max retries');
        }
    }
}
