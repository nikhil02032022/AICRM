<?php

declare(strict_types=1);

namespace App\Services\CRM\Application;

use App\Enums\CRM\ApplicationFormDraftStatus;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\ApplicationFormDraft;
use App\Models\CRM\ApplicationFormTemplate;
use App\Repositories\CRM\Application\ApplicationFormDraftRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// BRD: CRM-AP-003 — Service orchestration for application form save-and-resume
final class ApplicationFormDraftService
{
    private const APPLICATION_FEE_STATUS_NOT_REQUIRED = 'not_required';
    private const APPLICATION_FEE_STATUS_PENDING = 'pending';
    private const APPLICATION_FEE_STATUS_PAID = 'paid';

    public function __construct(
        private readonly ApplicationFormDraftRepositoryInterface $repository,
    ) {}

    /** @param array<string, mixed> $validated */
    public function createDraft(
        ApplicationFormTemplate $template,
        int $institutionId,
        ?int $createdBy,
        array $validated,
    ): ApplicationFormDraft {
        $this->ensureSaveAndResumeEnabled($template);
        $this->ensureMobileOptimisedEnabled($template);
        $feeConfiguration = $this->resolveFeeConfiguration($template);
        $selectedProgrammeUuids = $this->resolveSelectedProgrammeUuids(
            template: $template,
            institutionId: $institutionId,
            incomingProgrammeUuids: $validated['programme_uuids'] ?? null,
            existingProgrammeUuids: [],
        );

        $expiresInHours = (int) ($validated['expires_in_hours'] ?? 336);

        return $this->repository->create([
            'institution_id' => $institutionId,
            'campus_id' => $template->campus_id,
            'application_form_template_id' => $template->id,
            'resume_token' => $this->generateUniqueResumeToken(),
            'status' => ApplicationFormDraftStatus::DRAFT,
            'current_section_id' => $validated['current_section_id'] ?? null,
            'last_completed_section_order' => $validated['last_completed_section_order'] ?? null,
            'progress_percentage' => (int) ($validated['progress_percentage'] ?? 0),
            'form_data' => $validated['form_data'] ?? [],
            'selected_programme_uuids' => $selectedProgrammeUuids,
            'application_fee_amount' => $feeConfiguration['amount'],
            'application_fee_currency' => $feeConfiguration['currency'],
            'application_fee_status' => $feeConfiguration['status'],
            'application_fee_transaction_reference' => null,
            'application_fee_gateway' => null,
            'application_fee_paid_at' => null,
            'last_saved_at' => now(),
            'expires_at' => now()->addHours($expiresInHours),
            'created_by' => $createdBy,
        ]);
    }

    /** @param array<string, mixed> $validated */
    public function saveDraft(ApplicationFormDraft $draft, array $validated): ApplicationFormDraft
    {
        $this->ensureDraftIsEditable($draft);
        $this->ensureMobileOptimisedEnabled($draft->template);

        $selectedProgrammeUuids = $this->resolveSelectedProgrammeUuids(
            template: $draft->template,
            institutionId: (int) $draft->institution_id,
            incomingProgrammeUuids: $validated['programme_uuids'] ?? null,
            existingProgrammeUuids: is_array($draft->selected_programme_uuids) ? $draft->selected_programme_uuids : [],
        );

        $data = [
            'current_section_id' => $validated['current_section_id'] ?? $draft->current_section_id,
            'last_completed_section_order' => $validated['last_completed_section_order'] ?? $draft->last_completed_section_order,
            'progress_percentage' => array_key_exists('progress_percentage', $validated)
                ? (int) $validated['progress_percentage']
                : $draft->progress_percentage,
            'form_data' => array_key_exists('form_data', $validated)
                ? $validated['form_data']
                : $draft->form_data,
            'selected_programme_uuids' => $selectedProgrammeUuids,
            'last_saved_at' => now(),
        ];

        if (array_key_exists('expires_in_hours', $validated)) {
            $data['expires_at'] = now()->addHours((int) $validated['expires_in_hours']);
        }

        return $this->repository->update($draft, $data);
    }

    public function resumeDraft(string $resumeToken, int $institutionId): ApplicationFormDraft
    {
        $draft = $this->repository->findByResumeTokenOrFail($resumeToken, $institutionId);
        $this->ensureDraftIsReadable($draft);

        return $draft;
    }

    /** @param array<string, mixed> $validated */
    public function submitDraft(ApplicationFormDraft $draft, array $validated): ApplicationFormDraft
    {
        $this->ensureDraftIsEditable($draft);
        $this->ensureMobileOptimisedEnabled($draft->template);
        $this->ensureApplicationFeeRequirementIsSatisfied($draft);

        $effectiveFormData = array_key_exists('form_data', $validated)
            ? (is_array($validated['form_data']) ? $validated['form_data'] : [])
            : (is_array($draft->form_data) ? $draft->form_data : []);
        $this->ensureMandatoryFieldsSatisfied($draft->template, $effectiveFormData);

        $selectedProgrammeUuids = $this->resolveSelectedProgrammeUuids(
            template: $draft->template,
            institutionId: (int) $draft->institution_id,
            incomingProgrammeUuids: $validated['programme_uuids'] ?? null,
            existingProgrammeUuids: is_array($draft->selected_programme_uuids) ? $draft->selected_programme_uuids : [],
        );
        $this->ensureProgrammeRequirementIsSatisfied($draft->template, $selectedProgrammeUuids);

        $template = $draft->template;
        $minimumCompleteness = $template?->minimum_completeness_percentage ?? 100;
        $incomingProgress = array_key_exists('progress_percentage', $validated)
            ? (int) $validated['progress_percentage']
            : $draft->progress_percentage;

        if ($incomingProgress < $minimumCompleteness) {
            throw ValidationException::withMessages([
                'progress_percentage' => [
                    'Cannot submit draft below template minimum completeness threshold.',
                ],
            ]);
        }

        return $this->repository->update($draft, [
            'current_section_id' => $validated['current_section_id'] ?? $draft->current_section_id,
            'last_completed_section_order' => $validated['last_completed_section_order'] ?? $draft->last_completed_section_order,
            'progress_percentage' => $incomingProgress,
            'form_data' => $effectiveFormData,
            'selected_programme_uuids' => $selectedProgrammeUuids,
            'status' => ApplicationFormDraftStatus::SUBMITTED,
            'submitted_at' => now(),
            'last_saved_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $validated */
    public function payApplicationFee(ApplicationFormDraft $draft, array $validated = []): ApplicationFormDraft
    {
        $this->ensureDraftIsEditable($draft);
        $this->ensureMobileOptimisedEnabled($draft->template);

        if ($draft->application_fee_status === self::APPLICATION_FEE_STATUS_NOT_REQUIRED) {
            throw ValidationException::withMessages([
                'application_fee_status' => ['Application fee is not required for this draft template.'],
            ]);
        }

        return $this->repository->update($draft, [
            'application_fee_status' => self::APPLICATION_FEE_STATUS_PAID,
            'application_fee_transaction_reference' => $validated['transaction_reference'] ?? $this->generateFeeTransactionReference(),
            'application_fee_gateway' => $validated['gateway'] ?? 'online',
            'application_fee_paid_at' => now(),
            'last_saved_at' => now(),
        ]);
    }

    private function ensureSaveAndResumeEnabled(ApplicationFormTemplate $template): void
    {
        $settings = $template->settings ?? [];
        $allowed = (bool) ($settings['allow_save_and_resume'] ?? false);

        if (! $allowed) {
            throw ValidationException::withMessages([
                'settings.allow_save_and_resume' => [
                    'BRD CRM-AP-003 requires settings.allow_save_and_resume to be true on the selected template.',
                ],
            ]);
        }
    }

    private function ensureMobileOptimisedEnabled(ApplicationFormTemplate $template): void
    {
        $settings = $template->settings ?? [];
        $enabled = (bool) ($settings['mobile_optimised'] ?? true);

        if (! $enabled) {
            throw ValidationException::withMessages([
                'settings.mobile_optimised' => [
                    'BRD CRM-AP-006 requires application forms to be mobile-optimised before draft operations.',
                ],
            ]);
        }
    }

    private function ensureDraftIsReadable(ApplicationFormDraft $draft): void
    {
        if ($draft->expires_at instanceof Carbon && $draft->expires_at->isPast()) {
            if ($draft->status !== ApplicationFormDraftStatus::EXPIRED) {
                $this->repository->update($draft, [
                    'status' => ApplicationFormDraftStatus::EXPIRED,
                ]);
            }

            throw ValidationException::withMessages([
                'resume_token' => ['Draft has expired and can no longer be resumed.'],
            ]);
        }
    }

    private function ensureDraftIsEditable(ApplicationFormDraft $draft): void
    {
        $this->ensureDraftIsReadable($draft);

        if ($draft->status !== ApplicationFormDraftStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => ['Only drafts in draft status can be updated.'],
            ]);
        }
    }

    private function ensureApplicationFeeRequirementIsSatisfied(ApplicationFormDraft $draft): void
    {
        if ($draft->application_fee_status === self::APPLICATION_FEE_STATUS_PENDING) {
            throw ValidationException::withMessages([
                'application_fee_status' => [
                    'BRD CRM-AP-004 requires online application fee payment before submission for this template.',
                ],
            ]);
        }
    }

    /**
     * @param list<string>|null $incomingProgrammeUuids
     * @param list<string> $existingProgrammeUuids
     * @return list<string>
     */
    private function resolveSelectedProgrammeUuids(
        ApplicationFormTemplate $template,
        int $institutionId,
        ?array $incomingProgrammeUuids,
        array $existingProgrammeUuids,
    ): array {
        $selectedProgrammeUuids = $incomingProgrammeUuids ?? $existingProgrammeUuids;
        $selectedProgrammeUuids = array_values(array_unique(array_filter(
            array_map(static fn (mixed $value): string => trim((string) $value), $selectedProgrammeUuids),
            static fn (string $value): bool => $value !== '',
        )));

        if ($selectedProgrammeUuids === []) {
            return [];
        }

        $maxProgrammes = $this->resolveMaxProgrammesPerApplication($template);

        if (count($selectedProgrammeUuids) > $maxProgrammes) {
            throw ValidationException::withMessages([
                'programme_uuids' => [
                    "Selected programmes exceed allowed limit of {$maxProgrammes} for this template.",
                ],
            ]);
        }

        $existingProgrammeUuids = CrmProgramme::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->whereNotNull('erp_programme_uuid')
            ->whereIn('erp_programme_uuid', $selectedProgrammeUuids)
            ->pluck('erp_programme_uuid')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        if (count($existingProgrammeUuids) !== count($selectedProgrammeUuids)) {
            throw ValidationException::withMessages([
                'programme_uuids' => [
                    'One or more selected programmes are invalid for this institution or inactive.',
                ],
            ]);
        }

        return array_values($existingProgrammeUuids);
    }

    /** @param list<string> $selectedProgrammeUuids */
    private function ensureProgrammeRequirementIsSatisfied(
        ApplicationFormTemplate $template,
        array $selectedProgrammeUuids,
    ): void {
        $settings = $template->settings ?? [];
        $allowMultiProgramme = (bool) ($settings['allow_multi_programme_applications'] ?? false);

        if ($allowMultiProgramme && $selectedProgrammeUuids === []) {
            throw ValidationException::withMessages([
                'programme_uuids' => [
                    'BRD CRM-AP-005 requires at least one programme selection before submission.',
                ],
            ]);
        }
    }

    /**
     * BRD: CRM-AP-007 — enforce mandatory field completion for submitted sections.
     *
     * @param array<string, mixed> $formData
     */
    private function ensureMandatoryFieldsSatisfied(ApplicationFormTemplate $template, array $formData): void
    {
        $sections = $template->sections;

        if (! is_array($sections) || $sections === []) {
            return;
        }

        $errors = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $sectionId = (string) ($section['id'] ?? '');

            if ($sectionId === '' || ! array_key_exists($sectionId, $formData)) {
                continue;
            }

            $sectionData = $formData[$sectionId];

            if (! is_array($sectionData)) {
                continue;
            }

            $fields = $section['fields'] ?? [];

            if (! is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if (! is_array($field) || ! (bool) ($field['required'] ?? false)) {
                    continue;
                }

                $fieldId = (string) ($field['id'] ?? '');

                if ($fieldId === '') {
                    continue;
                }

                $fieldType = (string) ($field['type'] ?? 'text');
                $value = $sectionData[$fieldId] ?? null;

                if ($this->isFieldValueMissing($value, $fieldType)) {
                    $errors["form_data.{$sectionId}.{$fieldId}"] = [
                        'This field is mandatory as per AP-007 configuration.',
                    ];
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function isFieldValueMissing(mixed $value, string $fieldType): bool
    {
        if ($value === null) {
            return true;
        }

        if ($fieldType === 'checkbox') {
            return ! in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }

    private function resolveMaxProgrammesPerApplication(ApplicationFormTemplate $template): int
    {
        $settings = $template->settings ?? [];
        $allowMultiProgramme = (bool) ($settings['allow_multi_programme_applications'] ?? false);
        $configuredMax = isset($settings['max_programmes_per_application'])
            ? (int) $settings['max_programmes_per_application']
            : 1;

        if (! $allowMultiProgramme) {
            return 1;
        }

        return max(2, min(10, $configuredMax));
    }

    /**
     * @return array{amount: float|null, currency: string, status: string}
     */
    private function resolveFeeConfiguration(ApplicationFormTemplate $template): array
    {
        $settings = $template->settings ?? [];
        $feeEnabled = (bool) ($settings['application_fee_enabled'] ?? false);
        $feeAmount = isset($settings['application_fee_amount']) ? (float) $settings['application_fee_amount'] : 0.0;

        if (! $feeEnabled || $feeAmount <= 0) {
            return [
                'amount' => null,
                'currency' => 'INR',
                'status' => self::APPLICATION_FEE_STATUS_NOT_REQUIRED,
            ];
        }

        return [
            'amount' => $feeAmount,
            'currency' => strtoupper((string) ($settings['application_fee_currency'] ?? 'INR')),
            'status' => self::APPLICATION_FEE_STATUS_PENDING,
        ];
    }

    private function generateFeeTransactionReference(): string
    {
        return 'APFEE-'.strtoupper(Str::random(12));
    }

    private function generateUniqueResumeToken(): string
    {
        do {
            $token = Str::lower(Str::random(48));
        } while ($this->repository->resumeTokenExists($token));

        return $token;
    }
}
