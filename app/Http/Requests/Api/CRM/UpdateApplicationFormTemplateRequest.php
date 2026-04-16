<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

// BRD: CRM-AP-001 — Validation for updating application form templates
class UpdateApplicationFormTemplateRequest extends FormRequest
{
    /** @var list<string> */
    private const AP002_REQUIRED_SECTION_IDS = [
        'personal_details',
        'academic_history',
        'entrance_exam_scores',
        'co_curricular_activities',
        'declarations',
        'digital_signature',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('crm.applications.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'slug' => ['sometimes', 'string', 'max:120', 'regex:/^[a-z0-9\-]+$/'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'campus_id' => ['sometimes', 'nullable', 'integer', 'exists:campuses,id'],
            'is_active' => ['sometimes', 'boolean'],
            'minimum_completeness_percentage' => ['sometimes', 'integer', 'min:1', 'max:100'],

            'sections' => ['sometimes', 'array', 'min:1'],
            'sections.*.id' => ['required_with:sections', 'string', 'max:60'],
            'sections.*.title' => ['required_with:sections', 'string', 'max:160'],
            'sections.*.order' => ['required_with:sections', 'integer', 'min:1'],
            'sections.*.description' => ['nullable', 'string', 'max:300'],
            'sections.*.fields' => ['required_with:sections', 'array', 'min:1'],
            'sections.*.fields.*.id' => ['required_with:sections', 'string', 'max:60'],
            'sections.*.fields.*.type' => ['required_with:sections', Rule::in(['text', 'textarea', 'email', 'tel', 'number', 'date', 'select', 'checkbox', 'radio', 'file', 'signature'])],
            'sections.*.fields.*.label' => ['required_with:sections', 'string', 'max:160'],
            'sections.*.fields.*.required' => ['sometimes', 'boolean'],
            'sections.*.fields.*.placeholder' => ['nullable', 'string', 'max:180'],
            'sections.*.fields.*.options_raw' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'sections.*.fields.*.options' => ['sometimes', 'array'],
            'sections.*.fields.*.options.*' => ['string', 'max:200'],
            'sections.*.fields.*.show_if' => ['nullable', 'array'],
            'sections.*.fields.*.show_if.field' => ['sometimes', 'string', 'max:60'],
            'sections.*.fields.*.show_if.operator' => ['sometimes', Rule::in(['equals', 'not_equals', 'contains', 'in'])],
            'sections.*.fields.*.show_if.value' => ['sometimes'],

            'progression_rules' => ['sometimes', 'nullable', 'array'],
            'progression_rules.*.from_section' => ['required_with:progression_rules', 'string', 'max:60'],
            'progression_rules.*.to_section' => ['required_with:progression_rules', 'string', 'max:60'],
            'progression_rules.*.condition_field' => ['nullable', 'string', 'max:60'],
            'progression_rules.*.condition_operator' => ['nullable', Rule::in(['equals', 'not_equals', 'contains', 'in'])],
            'progression_rules.*.condition_value' => ['nullable'],

            'settings' => ['sometimes', 'nullable', 'array'],
            'settings.allow_save_and_resume' => ['sometimes', 'boolean'],
            'settings.mobile_optimised' => ['sometimes', 'boolean'],
            'settings.show_progress_bar' => ['sometimes', 'boolean'],
            'settings.application_fee_enabled' => ['sometimes', 'boolean'],
            'settings.application_fee_amount' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
            'settings.application_fee_currency' => ['sometimes', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'settings.allow_multi_programme_applications' => ['sometimes', 'boolean'],
            'settings.max_programmes_per_application' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->has('sections')) {
                return;
            }

            $sections = $this->input('sections', []);

            if (! is_array($sections) || $sections === []) {
                return;
            }

            $sectionIds = collect($sections)
                ->pluck('id')
                ->filter(static fn ($value): bool => is_string($value) && $value !== '')
                ->values();

            $missingSections = array_values(array_diff(self::AP002_REQUIRED_SECTION_IDS, $sectionIds->all()));

            if ($missingSections !== []) {
                $validator->errors()->add(
                    'sections',
                    'BRD CRM-AP-002 requires support for sections: '.implode(', ', self::AP002_REQUIRED_SECTION_IDS).'. Missing: '.implode(', ', $missingSections).'.'
                );
            }

            if ($sectionIds->count() !== $sectionIds->unique()->count()) {
                $validator->errors()->add('sections', 'Section IDs must be unique.');
            }

            $fieldIdsBySection = collect($sections)
                ->mapWithKeys(static function (array $section): array {
                    $sectionId = (string) ($section['id'] ?? 'unknown_section');
                    $fieldIds = collect($section['fields'] ?? [])
                        ->pluck('id')
                        ->filter(static fn ($value): bool => is_string($value) && $value !== '');

                    return [$sectionId => $fieldIds];
                });

            $sectionWithDuplicateFieldIds = $fieldIdsBySection
                ->first(static fn ($fieldIds): bool => $fieldIds->count() !== $fieldIds->unique()->count());

            if ($sectionWithDuplicateFieldIds !== null) {
                $validator->errors()->add('sections', 'Field IDs must be unique within each section.');
            }

            $hasDigitalSignatureField = collect($sections)
                ->pluck('fields')
                ->flatten(1)
                ->contains(static fn ($field): bool => is_array($field) && (($field['type'] ?? null) === 'signature'));

            if (! $hasDigitalSignatureField) {
                $validator->errors()->add('sections', 'BRD CRM-AP-002 requires at least one digital signature field (type: signature).');
            }

            $settings = $this->input('settings', []);
            $feeEnabled = (bool) ($settings['application_fee_enabled'] ?? false);
            $feeAmount = isset($settings['application_fee_amount']) ? (float) $settings['application_fee_amount'] : 0.0;

            if ($feeEnabled && $feeAmount <= 0) {
                $validator->errors()->add(
                    'settings.application_fee_amount',
                    'BRD CRM-AP-004 requires a positive application fee amount when application fee is enabled.'
                );
            }

            $mobileOptimised = (bool) ($settings['mobile_optimised'] ?? true);

            if (! $mobileOptimised) {
                $validator->errors()->add(
                    'settings.mobile_optimised',
                    'BRD CRM-AP-006 requires application forms to remain mobile-optimised.'
                );
            }

            $allowMultiProgramme = (bool) ($settings['allow_multi_programme_applications'] ?? false);
            $maxProgrammes = isset($settings['max_programmes_per_application'])
                ? (int) $settings['max_programmes_per_application']
                : 1;

            if ($allowMultiProgramme && $maxProgrammes < 2) {
                $validator->errors()->add(
                    'settings.max_programmes_per_application',
                    'BRD CRM-AP-005 requires max_programmes_per_application to be at least 2 when multi-programme applications are enabled.'
                );
            }
        });
    }
}
