<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-LQ-007 — Data for a counsellor score override
final readonly class ScoreOverrideDTO
{
    public function __construct(
        public string $leadUuid,
        public int    $overriddenScore,
        public string $reason,
        public int    $actorId,
    ) {}
}
