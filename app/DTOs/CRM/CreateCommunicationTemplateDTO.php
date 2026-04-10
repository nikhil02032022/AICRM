<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\TemplateType;

// BRD: CRM-CC-001 — DTO for creating a communication template
final readonly class CreateCommunicationTemplateDTO
{
    public function __construct(
        public readonly int $institutionId,
        public readonly ?int $campusId,
        public readonly string $name,
        public readonly CommunicationChannel $channel,
        public readonly TemplateType $type,
        public readonly ?string $subject,
        public readonly ?string $bodyHtml,
        public readonly string $bodyText,
        public readonly array $mergeTags,
        public readonly int $createdBy,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromArray(array $validated, int $institutionId, int $userId): self
    {
        return new self(
            institutionId: $institutionId,
            campusId: $validated['campus_id'] ?? null,
            name: $validated['name'],
            channel: CommunicationChannel::from($validated['channel']),
            type: TemplateType::from($validated['type']),
            subject: $validated['subject'] ?? null,
            bodyHtml: $validated['body_html'] ?? null,
            bodyText: $validated['body_text'],
            mergeTags: $validated['merge_tags'] ?? [],
            createdBy: $userId,
        );
    }
}
