<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\AttributionModel;

// BRD: CRM-LC-017 — Typed DTO for campaign spend entry creation.
final readonly class CreateCampaignSpendDTO
{
    public function __construct(
        public string $source,
        public ?string $campaignName,
        public string $periodStart,
        public string $periodEnd,
        public float $amount,
        public string $currency,
        public AttributionModel $attributionModel,
        public ?int $campusId,
        public ?string $notes,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        return new self(
            source: (string) $validated['source'],
            campaignName: $validated['campaign_name'] ?? null,
            periodStart: (string) $validated['period_start'],
            periodEnd: (string) $validated['period_end'],
            amount: (float) $validated['amount'],
            currency: (string) ($validated['currency'] ?? 'INR'),
            attributionModel: AttributionModel::from((string) ($validated['attribution_model'] ?? AttributionModel::LAST_TOUCH->value)),
            campusId: $validated['campus_id'] ?? null,
            notes: $validated['notes'] ?? null,
        );
    }
}
