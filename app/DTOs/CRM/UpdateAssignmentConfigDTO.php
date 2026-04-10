<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\AssignmentMode;

// BRD: CRM-EC-006 — DTO for updating the counsellor assignment configuration for an institution
final readonly class UpdateAssignmentConfigDTO
{
    public function __construct(
        public AssignmentMode $assignmentMode,
        public int $maxLeadsPerCounsellor,
        public int $escalationHours,
        public ?int $escalationToUserId,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        return new self(
            assignmentMode: AssignmentMode::from($validated['assignment_mode']),
            maxLeadsPerCounsellor: (int) $validated['max_leads_per_counsellor'],
            escalationHours: (int) $validated['escalation_hours'],
            escalationToUserId: isset($validated['escalation_to_user_id'])
                                        ? (int) $validated['escalation_to_user_id']
                                        : null,
        );
    }
}
