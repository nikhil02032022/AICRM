<?php

declare(strict_types=1);

namespace App\Services\CRM\Erp;

use App\Enums\CRM\ApplicationStatus;
use App\Jobs\CRM\ConvertToErpStudentJob;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\CRM\Campus;
use App\Models\CRM\CrmProgramme;
use App\Repositories\CRM\Application\ApplicationConversionLogRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// BRD: CRM-AP-016 — Orchestrates applicant-to-ERP-Student-Master conversion lifecycle
final class ErpConversionService
{
    public function __construct(
        private readonly ApplicationConversionLogRepositoryInterface $conversionLogRepository,
    ) {}

    /**
     * Check whether an application is eligible for ERP conversion.
     * Requires: OFFER_ACCEPTED status, an accepted offer letter, no existing successful log.
     */
    public function canConvert(Application $application): bool
    {
        if ($application->status !== ApplicationStatus::OFFER_ACCEPTED) {
            return false;
        }

        $offerLetter = $application->currentOfferLetter;
        if ($offerLetter === null || ! $offerLetter->isAccepted()) {
            return false;
        }

        $existingLog = $this->conversionLogRepository->findByApplicationUuid($application->uuid);
        if ($existingLog !== null && $existingLog->isSuccessful()) {
            return false;
        }

        return true;
    }

    /**
     * Build the ERP Student Master payload from CRM lead + application data.
     *
     * DPDP: mobile/email are decrypted here only for payload construction; not logged.
     *
     * @return array<string, mixed>
     */
    public function buildPayload(Application $application): array
    {
        $lead = $application->lead;
        $draft = $application->draft;

        $programmeCode = null;
        if ($draft !== null && ! empty($draft->selected_programme_uuids)) {
            $programme = CrmProgramme::withoutGlobalScopes()
                ->where('uuid', $draft->selected_programme_uuids[0])
                ->first();
            $programmeCode = $programme?->code;
        }

        $campusCode = null;
        if ($application->campus_id !== null) {
            $campus = Campus::withoutGlobalScopes()->find($application->campus_id);
            $campusCode = $campus?->code;
        }

        return [
            'first_name'              => $lead?->first_name ?? '',
            'last_name'               => $lead?->last_name ?? '',
            'email'                   => $lead?->email ?? '',
            'mobile'                  => $lead?->mobile ?? '',
            'programme_code'          => $programmeCode ?? '',
            'campus_code'             => $campusCode ?? '',
            'admission_year'          => (int) ($application->submitted_at?->year ?? now()->year),
            'crm_application_uuid'    => $application->uuid,
        ];
    }

    /**
     * Initiate ERP conversion: creates a pending audit log and dispatches the async job.
     *
     * BRD: CRM-AP-016 — Conversion is async; job handles ERP write + status transition.
     *
     * @throws ValidationException when application is not eligible for conversion
     */
    public function convert(Application $application, int $initiatedByUserId): ApplicationConversionLog
    {
        if (! $this->canConvert($application)) {
            throw ValidationException::withMessages([
                'application' => ['This application is not eligible for ERP conversion.'],
            ]);
        }

        $payload = $this->buildPayload($application);

        $log = $this->conversionLogRepository->create([
            'uuid'                 => Str::uuid()->toString(),
            'institution_id'       => $application->institution_id,
            'campus_id'            => $application->campus_id,
            'application_uuid'     => $application->uuid,
            'lead_uuid'            => $application->lead_uuid,
            'converted_by_user_id' => $initiatedByUserId,
            'status'               => 'pending',
            'attempted_at'         => now(),
            'conversion_payload'   => $payload,
        ]);

        ConvertToErpStudentJob::dispatch($log->uuid, $application->institution_id);

        return $log;
    }

    /**
     * Re-dispatch conversion for an eligible failed log.
     *
     * @throws ValidationException when log is not eligible for retry
     */
    public function retry(ApplicationConversionLog $log): ApplicationConversionLog
    {
        if (! $log->isEligibleForRetry()) {
            throw ValidationException::withMessages([
                'log' => ['This conversion log is not eligible for retry.'],
            ]);
        }

        $log = $this->conversionLogRepository->update($log, [
            'status'       => 'pending',
            'attempted_at' => now(),
            'error_message' => null,
        ]);

        ConvertToErpStudentJob::dispatch($log->uuid, $log->institution_id);

        return $log;
    }
}
