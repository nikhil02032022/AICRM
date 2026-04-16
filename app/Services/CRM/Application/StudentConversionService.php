<?php

declare(strict_types=1);

namespace App\Services\CRM\Application;

use App\Enums\CRM\LeadStatus;
use App\Events\CRM\Application\LeadConvertedToStudentEvent;
use App\Models\CRM\Application;
use App\Repositories\CRM\Application\ApplicationConversionLogRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * BRD: CRM-AP-016, CRM-AP-017 — Student Master conversion (lead → ERP student)
 * Maintains "zero re-entry" principle: all data extracted from CRM, no manual re-entry on ERP
 */
final class StudentConversionService
{
    public function __construct(
        private readonly ApplicationConversionLogRepositoryInterface $conversionLogRepository,
    ) {}

    /**
     * Initiate conversion of applicant to ERP Student Master.
     * Validates pre-conditions and dispatches queued job.
     * BRD: CRM-AP-016, CRM-AP-017
     */
    public function convert(
        Application $application,
        ?int $convertedByUserId = null,
    ): \App\Models\CRM\ApplicationConversionLog {
        // Validate pre-conditions
        $this->validateConversionPreConditions($application);

        // Build conversion payload (zero re-entry principle)
        $payload = $this->buildErpStudentMasterPayload($application);

        // Create conversion log entry (idempotent key)
        $conversionLog = $this->conversionLogRepository->create([
            'uuid' => Str::uuid(),
            'institution_id' => $application->institution_id,
            'campus_id' => $application->campus_id,
            'application_uuid' => $application->uuid,
            'lead_uuid' => $application->lead_uuid,
            'status' => 'pending',
            'conversion_payload' => $payload,
            'converted_by_user_id' => $convertedByUserId,
            'attempted_at' => now(),
        ]);

        // Dispatch async job to call ERP API
        \App\Jobs\CRM\ConvertToStudentJob::dispatch($conversionLog);

        return $conversionLog;
    }

    /**
     * Validate application meets all requirements for Student Master conversion.
     */
    private function validateConversionPreConditions(Application $application): void
    {
        // Offer must be accepted
        $currentOffer = $application->currentOfferLetter();
        if (! $currentOffer || ! $currentOffer->isAccepted()) {
            throw ValidationException::withMessages([
                'offer' => ['No accepted offer letter found. Applicant must accept an offer before conversion.'],
            ]);
        }

        // Application fee must be paid (if required)
        $draft = $application->draft;
        if ($draft && $draft->application_fee_status === 'pending') {
            throw ValidationException::withMessages([
                'fee' => ['Application fee is pending. Payment must be settled before conversion.'],
            ]);
        }

        // Lead must not already be converted
        $existingConversion = $this->conversionLogRepository->findByApplicationUuid($application->uuid);
        if ($existingConversion && $existingConversion->isSuccessful()) {
            throw ValidationException::withMessages([
                'conversion' => ['This application has already been converted to a Student record.'],
            ]);
        }
    }

    /**
     * Build DTO for ERP Student Master creation.
     * Extracts all data from Lead + ApplicationFormDraft + OfferLetter.
     * BRD: CRM-AP-017 — Zero re-entry principle
     */
    private function buildErpStudentMasterPayload(Application $application): array
    {
        $lead = $application->lead;
        $draft = $application->draft;
        $currentOffer = $application->currentOfferLetter();

        if (! $lead || ! $currentOffer) {
            throw new \LogicException('Lead and current offer required for conversion payload');
        }

        return [
            'lead_uuid' => $lead->uuid,
            'application_uuid' => $application->uuid,

            // Personal info (from lead)
            'first_name' => $lead->first_name,
            'middle_name' => $lead->middle_name ?? null,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'mobile' => $lead->mobile,
            'date_of_birth' => $lead->date_of_birth?->toDateString(),
            'gender' => $lead->gender ?? null,

            // Academic info (from draft)
            'form_data' => $draft?->form_data ?? [],

            // Admission info (from offer)
            'programme_uuid' => $currentOffer->programme_uuid,
            'admission_cycle_uuid' => $application->admission_cycle_uuid,

            // Timestamps for audit trail
            'lead_created_at' => $lead->created_at?->toIso8601String(),
            'application_submitted_at' => $application->submitted_at?->toIso8601String(),
            'offer_accepted_at' => $currentOffer->acceptance_recorded_at?->toIso8601String(),
            'conversion_initiated_at' => now()->toIso8601String(),
        ];
    }
}
