<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\ActivityType;

// BRD: CRM-EC-004 — Typed DTO for creating an activity / timeline entry
final readonly class CreateActivityDTO
{
    public function __construct(
        public ActivityType $type,
        public string $subjectType,
        public int $subjectId,
        public int $institutionId,
        public ?string $body,
        public ?string $channel,
        public ?string $direction,  // outbound | inbound | internal
        public ?array $metadata,   // DPDP: must never contain raw PII
        public ?int $performedById,
    ) {}
}
