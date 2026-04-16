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
    public function issue(
        Application $application,
        string $programmeUuid,
        ?string $expiryDays = null,
    ): OfferLetter {
        // Prevent duplicate pending offers for same application/programme
        $existingOffer = $this->repository->findByApplicationUuid($application->uuid);
        if ($existingOffer && ! $existingOffer->isExpired() && ! $existingOffer->isDeclined()) {
            throw ValidationException::withMessages([
                'offer' => ['A valid offer already exists for this application.'],
            ]);
        }

        $offerLetter = $this->repository->create([
            'uuid' => Str::uuid(),
            'institution_id' => $application->institution_id,
            'campus_id' => $application->campus_id,
            'application_uuid' => $application->uuid,
            'lead_uuid' => $application->lead_uuid,
            'programme_uuid' => $programmeUuid,
            'status' => 'pending',
            'expires_at' => $expiryDays
                ? now()->addDays((int) $expiryDays)
                : now()->addDays(30),
        ]);

        // Dispatch async job to generate PDF
        \App\Jobs\CRM\GenerateOfferLetterJob::dispatch($offerLetter);

        return $offerLetter;
    }

    /**
     * Send offer letter via specified channel.
     * BRD: CRM-AP-013
     */
    public function send(
        OfferLetter $offerLetter,
        string $channel = 'email',
    ): OfferLetter {
        if ($offerLetter->status !== 'generated') {
            throw ValidationException::withMessages([
                'status' => ['Offer cannot be sent until PDF is generated.'],
            ]);
        }

        return $this->repository->update($offerLetter, [
            'status' => 'sent',
            'sent_at' => now(),
            'sent_via' => $channel,
        ]);
    }

    /**
     * Record digital acceptance of offer with DPDP compliance.
     * BRD: CRM-AP-014, CRM-AP-015 — DPDP: capture IP and timestamp
     */
    public function recordAcceptance(
        OfferLetter $offerLetter,
        string $ipAddress,
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
