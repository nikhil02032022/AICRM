<?php

declare(strict_types=1);

namespace App\Services\CRM\Application;

use App\DTOs\CRM\SendEmailDTO;
use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\CommunicationChannel;
use App\Events\CRM\ApplicationStatusChangedEvent;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\ApplicationStatusHistory;
use App\Models\CRM\CommunicationTemplate;
use App\Models\CRM\DltTemplate;
use App\Repositories\CRM\Application\ApplicationRepositoryInterface;
use App\Services\CRM\Communication\EmailService;
use App\Services\CRM\Communication\SmsService;
use App\Services\CRM\Communication\WhatsAppService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// BRD: CRM-AP-008, CRM-AP-009, CRM-AP-011 — Application pipeline state management and seat availability
final class ApplicationPipelineService
{
    public function __construct(
        private readonly ApplicationRepositoryInterface $repository,
        private readonly EmailService $emailService,
        private readonly SmsService $smsService,
        private readonly WhatsAppService $whatsAppService,
    ) {}

    /**
     * Promote a submitted draft to the pipeline (create Application entity).
     * Called after ApplicationFormDraft submission.
     * BRD: CRM-AP-008
     */
    public function promoteFromDraft(\App\Models\CRM\ApplicationFormDraft $draft): Application
    {
        // Check if application already exists for this draft (idempotent)
        if ($draft->lead_uuid && $existingApp = $this->repository->findByLeadUuidOrFail($draft->lead_uuid)) {
            return $existingApp;
        }

        return $this->repository->create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'institution_id' => $draft->institution_id,
            'campus_id' => $draft->campus_id,
            'lead_uuid' => $draft->lead_uuid ?? throw new \LogicException('Draft must have lead_uuid for promotion'),
            'application_form_draft_uuid' => $draft->uuid,
            'status' => ApplicationStatus::UNDER_REVIEW,
            'stage_entered_at' => now(),
            'submitted_at' => now(),
        ]);
    }

    /**
     * Transition an application to a new pipeline stage with validation and audit.
     * BRD: CRM-AP-009 — Full status transition audit history maintained
     */
    public function transition(
        Application $application,
        ApplicationStatus $newStatus,
        ?int $changedByUserId = null,
        ?string $reason = null,
    ): Application {
        $currentStatus = $application->status;

        // Validate transition is allowed
        if (! $application->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => [
                    "Cannot transition from {$currentStatus->label()} to {$newStatus->label()}",
                ],
            ]);
        }

        // Update application status
        $application = $this->repository->update($application, [
            'status' => $newStatus,
            'stage_entered_at' => now(),
        ]);

        // Record audit history
        ApplicationStatusHistory::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'institution_id' => $application->institution_id,
            'application_uuid' => $application->uuid,
            'from_status' => $currentStatus->value,
            'to_status' => $newStatus->value,
            'changed_by_user_id' => $changedByUserId,
            'reason' => $reason,
        ]);

        // Fire event for listeners (audit logging, notifications, etc.)
        ApplicationStatusChangedEvent::dispatch(
            $application,
            $currentStatus->value,
            $newStatus->value,
            $reason,
            $changedByUserId,
        );

        return $application;
    }

    /**
     * Check seat availability for a programme.
     * BRD: CRM-AP-011 — Seat availability visibility
     * Returns: total_seats, application_count, allocated_seats, available_seats
     */
    public function checkSeatAvailability(string $programmeUuid): array
    {
        $programme = CrmProgramme::query()
            ->where('erp_programme_uuid', $programmeUuid)
            ->firstOrFail();

        $applicationCounts = $this->applicationCountsByProgramme((int) $programme->institution_id);

        return $this->buildSeatAvailabilityRecord(
            $programme,
            (int) ($applicationCounts[$programme->id] ?? 0),
        );
    }

    /**
     * Get seat availability across active programmes for the current institution.
     * BRD: CRM-AP-011 — Programme-wise seat availability vs application count
     *
     * @return array<int, array<string, int|float|string|null>>
     */
    public function seatAvailabilityOverview(int $institutionId): array
    {
        $programmes = CrmProgramme::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'institution_id', 'erp_programme_uuid', 'intake_capacity']);

        $applicationCounts = $this->applicationCountsByProgramme($institutionId);

        return $programmes
            ->map(fn (CrmProgramme $programme): array => $this->buildSeatAvailabilityRecord(
                $programme,
                (int) ($applicationCounts[$programme->id] ?? 0),
            ))
            ->all();
    }

    /**
     * Get application count by status (for funnel analytics).
     */
    public function countByStatus(int $institutionId, array $filters = []): array
    {
        $baseQuery = Application::where('institution_id', $institutionId);

        if (isset($filters['admission_cycle_uuid'])) {
            $baseQuery->where('admission_cycle_uuid', $filters['admission_cycle_uuid']);
        }

        $counts = [];
        foreach (ApplicationStatus::cases() as $status) {
            $counts[$status->value] = $baseQuery->where('status', $status)->count();
        }

        return $counts;
    }

    /**
     * BRD: CRM-AP-010 — Bulk status update for selected applications.
     *
     * @param array<int, string> $applicationUuids
     * @return array{updated:int, skipped:int}
     */
    public function bulkUpdateStatus(array $applicationUuids, ApplicationStatus $targetStatus, ?int $changedByUserId = null, ?string $reason = null): array
    {
        $applications = $this->repository->findManyByUuids($applicationUuids);

        $updated = 0;
        $skipped = 0;

        foreach ($applications as $application) {
            try {
                $this->transition($application, $targetStatus, $changedByUserId, $reason);
                $updated++;
            } catch (ValidationException) {
                $skipped++;
            }
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * BRD: CRM-AP-010 — Bulk counsellor assignment for selected applications.
     *
     * @param array<int, string> $applicationUuids
     */
    public function bulkAssignCounsellor(array $applicationUuids, int $counsellorId): int
    {
        return $this->repository->bulkAssignCounsellorByUuids($applicationUuids, $counsellorId);
    }

    /**
     * BRD: CRM-AP-010 — Bulk communication dispatch for selected applications.
     *
     * @param array<int, string> $applicationUuids
     * @param array<string, mixed> $payload
     * @return array{sent:int, skipped:int}
     */
    public function bulkSendCommunication(array $applicationUuids, array $payload): array
    {
        $applications = $this->repository->findManyByUuids($applicationUuids);
        $channel = CommunicationChannel::from((string) $payload['channel']);

        $sent = 0;
        $skipped = 0;

        foreach ($applications as $application) {
            $lead = $application->lead;

            if ($lead === null) {
                $skipped++;
                continue;
            }

            try {
                match ($channel) {
                    CommunicationChannel::EMAIL => $this->sendBulkEmail($lead, $payload),
                    CommunicationChannel::SMS => $this->sendBulkSms($lead, $payload),
                    CommunicationChannel::WHATSAPP => $this->sendBulkWhatsApp($lead, $payload),
                    default => throw new \RuntimeException('Unsupported communication channel for AP-010 bulk action.'),
                };

                $sent++;
            } catch (\Throwable) {
                // Continue processing remaining leads; caller receives sent vs skipped metrics.
                $skipped++;
            }
        }

        return [
            'sent' => $sent,
            'skipped' => $skipped,
        ];
    }

    /**
     * BRD: CRM-AP-010 — Build export rows for selected applications.
     *
     * @param array<int, string> $applicationUuids
     * @return array<int, array<string, string>>
     */
    public function buildExportRows(array $applicationUuids): array
    {
        $applications = $this->repository->findManyByUuids($applicationUuids);

        return $applications->map(static function (Application $application): array {
            $lead = $application->lead;

            return [
                'application_uuid' => (string) $application->uuid,
                'lead_uuid' => (string) $application->lead_uuid,
                'applicant_name' => trim((string) (($lead?->first_name ?? '').' '.($lead?->last_name ?? ''))),
                'applicant_email' => (string) ($lead?->email ?? ''),
                'source' => (string) ($lead?->source?->value ?? ''),
                'lead_score' => (string) ($lead?->lead_score ?? ''),
                'status' => (string) $application->status->value,
                'assigned_counsellor' => (string) ($application->assignedCounsellor?->name ?? ''),
                'submitted_at' => (string) optional($application->submitted_at)->toDateTimeString(),
            ];
        })->all();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sendBulkEmail(\App\Models\CRM\Lead $lead, array $payload): void
    {
        $dto = new SendEmailDTO(
            templateId: (int) Arr::get($payload, 'template_id', 0),
            fromName: (string) Arr::get($payload, 'from_name', 'Admissions Team'),
            fromEmail: (string) Arr::get($payload, 'from_email', 'no-reply@example.test'),
            subject: Arr::get($payload, 'subject'),
            customBodyHtml: Arr::get($payload, 'custom_body_html'),
            channel: CommunicationChannel::EMAIL,
        );

        $this->emailService->sendToLead($lead, $dto);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sendBulkSms(\App\Models\CRM\Lead $lead, array $payload): void
    {
        $template = DltTemplate::query()->findOrFail((int) $payload['dlt_template_id']);
        $message = (string) Arr::get($payload, 'message', $template->template_body);

        $this->smsService->sendToLead($lead, $message, $template);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sendBulkWhatsApp(\App\Models\CRM\Lead $lead, array $payload): void
    {
        $templateName = (string) Arr::get($payload, 'whatsapp_template_name', '');

        if ($templateName === '' && Arr::has($payload, 'template_id')) {
            $template = CommunicationTemplate::query()->findOrFail((int) $payload['template_id']);
            $templateName = $template->name;
        }

        if ($templateName === '') {
            throw new \RuntimeException('WhatsApp template is required for bulk communication.');
        }

        $this->whatsAppService->sendTemplate($lead, $templateName, []);
    }

    /**
     * @return array<int, int>
     */
    private function applicationCountsByProgramme(int $institutionId): array
    {
        return Application::query()
            ->selectRaw('lead_programme_interests.crm_programme_id, COUNT(DISTINCT applications.uuid) as application_count')
            ->join('leads', 'leads.uuid', '=', 'applications.lead_uuid')
            ->join('lead_programme_interests', function ($join): void {
                $join->on('lead_programme_interests.lead_id', '=', 'leads.id')
                    ->where('lead_programme_interests.is_primary', '=', true);
            })
            ->where('applications.institution_id', $institutionId)
            ->groupBy('lead_programme_interests.crm_programme_id')
            ->pluck('application_count', 'lead_programme_interests.crm_programme_id')
            ->map(static fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * @return array<string, int|float|string|null>
     */
    private function buildSeatAvailabilityRecord(CrmProgramme $programme, int $applicationCount): array
    {
        $totalSeats = max(0, (int) ($programme->intake_capacity ?? 0));
        $availableSeats = max(0, $totalSeats - $applicationCount);
        $utilisationPercentage = $totalSeats > 0
            ? round(($applicationCount / $totalSeats) * 100, 2)
            : 0.0;

        $capacityStatus = match (true) {
            $totalSeats === 0 => 'not_configured',
            $applicationCount >= $totalSeats => 'full',
            $utilisationPercentage >= 80 => 'critical',
            $utilisationPercentage >= 50 => 'warning',
            default => 'healthy',
        };

        $capacityStatusLabel = match ($capacityStatus) {
            'full' => 'Full',
            'critical' => 'Critical',
            'warning' => 'Watch',
            'not_configured' => 'Not Configured',
            default => 'Healthy',
        };

        return [
            'programme_uuid' => $programme->erp_programme_uuid,
            'programme_name' => $programme->name,
            'programme_code' => $programme->code,
            'total_seats' => $totalSeats,
            'application_count' => $applicationCount,
            'allocated_seats' => $applicationCount,
            'available_seats' => $availableSeats,
            'utilisation_percentage' => $utilisationPercentage,
            'capacity_status' => $capacityStatus,
            'capacity_status_label' => $capacityStatusLabel,
        ];
    }
}
