<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-AP-001 — Typed DTO for application form template creation and update
final readonly class CreateApplicationFormTemplateDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<int, array<string, mixed>>|null  $progressionRules
     * @param  array<string, mixed>|null  $settings
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description,
        public array $sections,
        public ?array $progressionRules,
        public ?array $settings,
        public int $minimumCompletenessPercentage,
        public bool $isActive,
        public ?int $campusId,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
            slug: $validated['slug'],
            description: $validated['description'] ?? null,
            sections: self::normaliseSections($validated['sections'] ?? []),
            progressionRules: $validated['progression_rules'] ?? null,
            settings: $validated['settings'] ?? null,
            minimumCompletenessPercentage: (int) ($validated['minimum_completeness_percentage'] ?? 100),
            isActive: (bool) ($validated['is_active'] ?? true),
            campusId: isset($validated['campus_id']) ? (int) $validated['campus_id'] : null,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    private static function normaliseSections(array $sections): array
    {
        return array_values(array_map(static function (array $section): array {
            $fields = array_values(array_map(static function (array $field): array {
                if (isset($field['options_raw']) && is_string($field['options_raw']) && $field['options_raw'] !== '') {
                    $field['options'] = array_values(array_filter(
                        array_map('trim', explode(',', $field['options_raw']))
                    ));
                }

                unset($field['options_raw']);

                return $field;
            }, $section['fields'] ?? []));

            $section['fields'] = $fields;

            return $section;
        }, $sections));
    }
}
