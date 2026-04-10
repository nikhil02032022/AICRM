<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\LeadSource;

// BRD: CRM-LC-001 — Typed DTO carrying validated web form configuration data
final readonly class CreateWebFormDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public array $fields,
        public LeadSource $source,
        public bool $isActive,
        public ?string $redirectUrl,
        public string $consentFormVersion,
        public ?string $accentColor,
        public ?string $logoUrl,
        public ?int $campusId,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
            slug: $validated['slug'],
            fields: array_map(static function (array $field): array {
                if (isset($field['options_raw']) && is_string($field['options_raw']) && $field['options_raw'] !== '') {
                    $field['options'] = array_values(array_filter(
                        array_map('trim', explode(',', $field['options_raw']))
                    ));
                }
                unset($field['options_raw']);

                return $field;
            }, $validated['fields'] ?? []),
            source: LeadSource::from($validated['source'] ?? LeadSource::WEBSITE_ORGANIC->value),
            isActive: (bool) ($validated['is_active'] ?? true),
            redirectUrl: $validated['redirect_url'] ?? null,
            consentFormVersion: $validated['consent_form_version'],
            accentColor: $validated['accent_color'] ?? null,
            logoUrl: $validated['logo_url'] ?? null,
            campusId: isset($validated['campus_id']) ? (int) $validated['campus_id'] : null,
        );
    }
}
