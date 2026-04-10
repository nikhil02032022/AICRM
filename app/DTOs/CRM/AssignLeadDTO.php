<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-EC-007 — DTO for reassigning a lead to a counsellor (manual assignment)
final readonly class AssignLeadDTO
{
    public function __construct(
        public string $leadUuid,
        public int $counsellorId,
        public ?string $reason,
        public int $performedByUserId,
    ) {}
}
