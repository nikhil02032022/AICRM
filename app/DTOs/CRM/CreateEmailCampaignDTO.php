<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-CC-002 — DTO for creating an email campaign
final readonly class CreateEmailCampaignDTO
{
    public function __construct(
        public readonly int $institutionId,
        public readonly ?int $campusId,
        public readonly string $name,
        public readonly string $subject,
        public readonly int $templateId,
        public readonly string $fromName,
        public readonly string $fromEmail,
        public readonly array $recipientFilter,
        public readonly ?string $scheduledAt,
        public readonly int $createdBy,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromArray(array $validated, int $institutionId, int $userId): self
    {
        return new self(
            institutionId: $institutionId,
            campusId: $validated['campus_id'] ?? null,
            name: $validated['name'],
            subject: $validated['subject'],
            templateId: (int) $validated['template_id'],
            fromName: $validated['from_name'],
            fromEmail: $validated['from_email'],
            recipientFilter: $validated['recipient_filter'] ?? [],
            scheduledAt: $validated['scheduled_at'] ?? null,
            createdBy: $userId,
        );
    }
}
