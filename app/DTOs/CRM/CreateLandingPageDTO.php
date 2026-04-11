<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\LandingPageStatus;

// BRD: CRM-LC-005 — Typed DTO for landing page creation and update payloads
final readonly class CreateLandingPageDTO
{
    /**
     * @param  array<int, array<string, string|null>>|null  $content
     * @param  array<string, string|null>|null  $attributionParams
     */
    public function __construct(
        public string $name,
        public string $slug,
        public LandingPageStatus $status,
        public string $themeVariant,
        public string $headline,
        public ?string $subheadline,
        public ?string $heroImageUrl,
        public string $ctaLabel,
        public ?string $ctaSecondaryLabel,
        public ?array $content,
        public ?array $attributionParams,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?int $webFormId,
        public ?int $campusId,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        $sections = null;

        if (isset($validated['content']) && is_array($validated['content'])) {
            $sections = array_values(array_map(static function (array $section, int $index): array {
                return [
                    'id' => isset($section['id']) && is_string($section['id']) ? $section['id'] : 'block-'.$index,
                    'type' => isset($section['type']) && is_string($section['type']) ? $section['type'] : 'value_card',
                    'order' => isset($section['order']) ? (int) $section['order'] : $index,
                    'eyebrow' => $section['eyebrow'] ?? null,
                    'title' => $section['title'] ?? null,
                    'body' => $section['body'] ?? null,
                    'metric_label' => $section['metric_label'] ?? null,
                    'metric_value' => $section['metric_value'] ?? null,
                    'question' => $section['question'] ?? null,
                    'answer' => $section['answer'] ?? null,
                ];
            }, $validated['content'], array_keys($validated['content'])));
        }

        return new self(
            name: $validated['name'],
            slug: $validated['slug'] ?? '',
            status: LandingPageStatus::from($validated['status'] ?? LandingPageStatus::DRAFT->value),
            themeVariant: $validated['theme_variant'] ?? 'scholar',
            headline: $validated['headline'],
            subheadline: $validated['subheadline'] ?? null,
            heroImageUrl: $validated['hero_image_url'] ?? null,
            ctaLabel: $validated['cta_label'] ?? 'Submit enquiry',
            ctaSecondaryLabel: $validated['cta_secondary_label'] ?? null,
            content: $sections,
            attributionParams: isset($validated['attribution_params']) && is_array($validated['attribution_params'])
                ? array_filter($validated['attribution_params'], static fn (mixed $value): bool => $value !== null && $value !== '')
                : null,
            seoTitle: $validated['seo_title'] ?? null,
            seoDescription: $validated['seo_description'] ?? null,
            webFormId: isset($validated['web_form_id']) ? (int) $validated['web_form_id'] : null,
            campusId: isset($validated['campus_id']) ? (int) $validated['campus_id'] : null,
        );
    }
}