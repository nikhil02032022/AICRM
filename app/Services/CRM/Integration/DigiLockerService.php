<?php

declare(strict_types=1);

namespace App\Services\CRM\Integration;

use App\Enums\CRM\DigiLockerStatus;
use App\Events\CRM\DigiLockerVerifiedEvent;
use App\Jobs\CRM\VerifyDigiLockerDocumentJob;
use App\Models\CRM\DigiLockerDocument;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Integration\DigiLockerRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

// BRD: CRM-DM-006 — DigiLocker API Setu integration service
final class DigiLockerService
{
    public function __construct(
        private readonly DigiLockerRepositoryInterface $repository
    ) {}

    /**
     * BRD: CRM-DM-006 — List all DigiLocker documents for an institution (paginated)
     */
    public function list(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($institutionId, $perPage);
    }

    /**
     * BRD: CRM-DM-006 — List documents for a specific lead
     */
    public function forLead(int $leadId, int $institutionId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findByLead($leadId, $institutionId);
    }

    /**
     * BRD: CRM-DM-006 — Initiate a DigiLocker document request for a lead
     * Dispatches VerifyDigiLockerDocumentJob for async API Setu call
     */
    public function initiateRequest(Lead $lead, string $documentType, int $consentRecordId): DigiLockerDocument
    {
        $document = $this->repository->create([
            'uuid'              => (string) Str::uuid(),
            'institution_id'    => $lead->institution_id,
            'campus_id'         => $lead->campus_id,
            'lead_id'           => $lead->id,
            'status'            => DigiLockerStatus::REQUESTED,
            'document_type'     => $documentType,
            'consent_record_id' => $consentRecordId,
        ]);

        // BRD: CRM-DM-006 — Async verification via API Setu (never synchronous in a web request)
        VerifyDigiLockerDocumentJob::dispatch($document->id)->onQueue('crm-integrations');

        return $document;
    }

    /**
     * BRD: CRM-DM-006 — Mark document as verified after API Setu callback
     */
    public function markVerified(DigiLockerDocument $document, string $digilockerUri, string $storagePath): DigiLockerDocument
    {
        $updated = $this->repository->update($document, [
            'status'        => DigiLockerStatus::VERIFIED,
            'digilocker_uri'=> $digilockerUri,
            'storage_path'  => $storagePath,
            'is_verified'   => true,
            'verified_at'   => now(),
            'error_message' => null,
        ]);

        // BRD: CRM-DM-006 — Dispatch event on verification
        DigiLockerVerifiedEvent::dispatch($updated);

        return $updated;
    }

    /**
     * BRD: CRM-DM-006 — Mark document as failed with error detail
     */
    public function markFailed(DigiLockerDocument $document, string $error): DigiLockerDocument
    {
        return $this->repository->update($document, [
            'status'        => DigiLockerStatus::FAILED,
            'error_message' => $error,
        ]);
    }

    /**
     * BRD: CRM-DM-006 — Find by UUID
     */
    public function findByUuid(string $uuid): ?DigiLockerDocument
    {
        return $this->repository->findByUuid($uuid);
    }
}
