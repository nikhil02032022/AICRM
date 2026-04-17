<?php

declare(strict_types=1);

namespace App\Services\CRM\Application;

use App\Models\CRM\Application;
use App\Models\CRM\OfferLetter;
use App\Repositories\CRM\Application\OfferLetterRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// BRD: CRM-AP-012, CRM-AP-013, CRM-AP-014, CRM-AP-015 — Offer letter lifecycle management
final class OfferLetterService
{
    public function __construct(
        private readonly OfferLetterRepositoryInterface $repository,
    ) {}

    /**
     * Issue a new offer letter for an applicant.
     * Dispatches async PDF generation job.
     * BRD: CRM-AP-012, CRM-AP-013
     */
    /**
     * Issue a new offer letter for an applicant (supports conditional offers).
     * @param array $extraFields (conditional, required_documents)
     */
    public function issue(
        Application $application,
        string $programmeUuid,
        ?\DateTimeInterface $expiresAt = null,
        ?string $reason = null,
        array $extraFields = []
    ): OfferLetter {
        // Prevent duplicate pending offers for same application/programme
        $existingOffer = $this->repository->findByApplicationUuid($application->uuid);
        if ($existingOffer && ! $existingOffer->isExpired() && ! $existingOffer->isDeclined()) {
            throw ValidationException::withMessages([
                'offer' => ['A valid offer already exists for this application.'],
            ]);
        }

        $data = [
            'uuid' => Str::uuid(),
            'institution_id' => $application->institution_id,
            'campus_id' => $application->campus_id,
            'application_uuid' => $application->uuid,
            'lead_uuid' => $application->lead_uuid,
            'programme_uuid' => $programmeUuid,
            'status' => 'pending',
            'expires_at' => $expiresAt ?? now()->addDays(30),
            'conditional' => $extraFields['conditional'] ?? false,
            'required_documents' => $extraFields['required_documents'] ?? [],
            'document_verification_status' => $extraFields['conditional'] ? array_fill_keys(($extraFields['required_documents'] ?? []), false) : [],
            'decline_reason' => $reason,
        ];

        $offerLetter = $this->repository->create($data);

        // Dispatch async job to generate PDF
        \App\Jobs\CRM\GenerateOfferLetterJob::dispatch($offerLetter);

        return $offerLetter;
    }

    /**
     * Send offer letter via specified channel.
     * BRD: CRM-AP-013
     */
    /**
     * Dispatch async job to deliver offer letter via selected channel.
     * BRD: CRM-AP-013
     */
    public function send(
        OfferLetter $offerLetter,
        string $channel = 'email',
    ): void {
        if ($offerLetter->status !== 'generated') {
            throw ValidationException::withMessages([
                'status' => ['Offer cannot be sent until PDF is generated.'],
            ]);
        }
        \App\Jobs\CRM\SendOfferLetterJob::dispatch($offerLetter, $channel);
    }

    /**
     * Record digital acceptance of offer with DPDP compliance.
     * BRD: CRM-AP-014, CRM-AP-015 — DPDP: capture IP and timestamp
     */
    public function recordAcceptance(
        OfferLetter $offerLetter,
        string $ipAddress,
        ?string $notes = null,
    ): OfferLetter {
        if (! $offerLetter->isValidForAcceptance()) {
            throw ValidationException::withMessages([
                'status' => ['Offer is not valid for acceptance (expired, already accepted, or declined).'],
            ]);
        }

        return $this->repository->update($offerLetter, [
            'status' => 'accepted',
            'acceptance_recorded_at' => now(),
            'acceptance_ip' => $ipAddress,
        ]);
    }

    /**
     * Record decline of offer.
     * BRD: CRM-AP-015
     */
    public function recordDecline(
        OfferLetter $offerLetter,
        ?string $reason = null,
        ?string $ipAddress = null,
    ): OfferLetter {
        if (! $offerLetter->isValidForAcceptance()) {
            throw ValidationException::withMessages([
                'status' => ['Offer cannot be declined if already accepted or expired.'],
            ]);
        }

        return $this->repository->update($offerLetter, [
            'status' => 'declined',
            'declined_at' => now(),
            'decline_reason' => $reason,
        ]);
    }

    /**
     * Mark a required document as verified (or unverified) on a conditional offer.
     * BRD: CRM-AP-014
     */
    public function verifyDocument(
        OfferLetter $offerLetter,
        string $docType,
        bool $verified = true,
    ): OfferLetter {
        if (! $offerLetter->isConditional()) {
            throw ValidationException::withMessages([
                'offer' => ['Document verification only applies to conditional offers.'],
            ]);
        }

        $required = $offerLetter->getRequiredDocuments();
        if (! in_array($docType, $required, true)) {
            throw ValidationException::withMessages([
                'doc_type' => ["'{$docType}' is not a required document for this offer."],
            ]);
        }

        $status = $offerLetter->getDocumentVerificationStatus();
        $status[$docType] = $verified;

        return $this->repository->update($offerLetter, [
            'document_verification_status' => $status,
        ]);
    }

    /**
     * Generate a time-limited public acceptance token for the student portal.
     * BRD: CRM-AP-015
     */
    public function generateAcceptanceToken(OfferLetter $offerLetter, int $expiryHours = 72): string
    {
        if (! $offerLetter->isValidForAcceptance()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot generate portal link for an offer that is expired, accepted, or declined.'],
            ]);
        }

        $token = \Illuminate\Support\Str::random(64);

        $this->repository->update($offerLetter, [
            'acceptance_token' => $token,
            'acceptance_token_expires_at' => now()->addHours($expiryHours),
        ]);

        return $token;
    }

    /**
     * Resolve an offer by its public acceptance token.
     * Returns null if token is invalid or expired.
     * BRD: CRM-AP-015
     */
    public function resolveByToken(string $token): ?OfferLetter
    {
        return OfferLetter::withoutGlobalScopes()
            ->where('acceptance_token', $token)
            ->where('acceptance_token_expires_at', '>', now())
            ->first();
    }

    /**
     * Expire an offer (called by scheduler or manual action).
     */
    public function expire(OfferLetter $offerLetter): OfferLetter
    {
        if ($offerLetter->isAccepted()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot expire an offer that has already been accepted.'],
            ]);
        }

        return $this->repository->update($offerLetter, [
            'status' => 'expired',
        ]);
    }
}
