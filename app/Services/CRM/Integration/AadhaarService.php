<?php

declare(strict_types=1);

namespace App\Services\CRM\Integration;

use App\Enums\CRM\AadhaarKycStatus;
use App\Events\CRM\AadhaarKycCompletedEvent;
use App\Jobs\CRM\ProcessAadhaarKycJob;
use App\Models\CRM\AadhaarEkycLog;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Integration\AadhaarRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

// BRD: CRM-DM-007 — Aadhaar eKYC via API Setu OTP-based verification service
final class AadhaarService
{
    public function __construct(
        private readonly AadhaarRepositoryInterface $repository
    ) {}

    /**
     * BRD: CRM-DM-007 — List all Aadhaar eKYC logs for an institution (paginated)
     */
    public function list(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($institutionId, $perPage);
    }

    /**
     * BRD: CRM-DM-007 — List logs for a specific lead
     */
    public function forLead(int $leadId, int $institutionId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findByLead($leadId, $institutionId);
    }

    /**
     * BRD: CRM-DM-007 — Initiate Aadhaar eKYC session for a lead (consent captured before calling)
     * Dispatches ProcessAadhaarKycJob for async OTP send via API Setu
     * Note: Aadhaar number is NEVER stored — only transaction reference (DPDP)
     */
    public function initiate(Lead $lead, string $consentIp): AadhaarEkycLog
    {
        $log = $this->repository->create([
            'uuid'           => (string) Str::uuid(),
            'institution_id' => $lead->institution_id,
            'campus_id'      => $lead->campus_id,
            'lead_id'        => $lead->id,
            'status'         => AadhaarKycStatus::INITIATED,
            'consent_ip'     => $consentIp,
            'consent_at'     => now(),
        ]);

        // BRD: CRM-DM-007 — OTP dispatch is async — never synchronous in web request
        ProcessAadhaarKycJob::dispatch($log->id)->onQueue('crm-integrations');

        return $log;
    }

    /**
     * BRD: CRM-DM-007 — Verify OTP and complete KYC
     * nameMatch: whether applicant's name matches the Aadhaar record (no PII stored)
     */
    public function verifyOtp(AadhaarEkycLog $log, bool $nameMatch): AadhaarEkycLog
    {
        $updated = $this->repository->update($log, [
            'status'           => AadhaarKycStatus::VERIFIED,
            'name_match'       => $nameMatch,
            'kyc_complete'     => true,
            'kyc_completed_at' => now(),
            'error_message'    => null,
        ]);

        // BRD: CRM-DM-007 — Dispatch event for downstream listeners
        AadhaarKycCompletedEvent::dispatch($updated);

        return $updated;
    }

    /**
     * BRD: CRM-DM-007 — Mark session as failed
     */
    public function markFailed(AadhaarEkycLog $log, string $error): AadhaarEkycLog
    {
        return $this->repository->update($log, [
            'status'        => AadhaarKycStatus::FAILED,
            'error_message' => $error,
        ]);
    }

    /**
     * BRD: CRM-DM-007 — Find by UUID
     */
    public function findByUuid(string $uuid): ?AadhaarEkycLog
    {
        return $this->repository->findByUuid($uuid);
    }
}
