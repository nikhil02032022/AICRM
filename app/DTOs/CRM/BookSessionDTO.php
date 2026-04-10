<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\SessionType;
use Carbon\Carbon;

// BRD: CRM-EC-015 — Book session DTO (internal staff flow)
// BRD: CRM-EC-016 — Public booking DTO shares the same structure
final readonly class BookSessionDTO
{
    public function __construct(
        public readonly int $leadId,
        public readonly int $counsellorId,
        public readonly SessionType $sessionType,
        public readonly Carbon $scheduledAt,
        public readonly string $mode,           // online | offline | phone
        public readonly ?string $preSessionNotes = null,
        public readonly ?string $availabilitySlotId = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            leadId: (int) $validated['lead_id'],
            counsellorId: (int) $validated['counsellor_id'],
            sessionType: SessionType::from($validated['session_type']),
            scheduledAt: Carbon::parse($validated['scheduled_at']),
            mode: $validated['mode'] ?? 'online',
            preSessionNotes: $validated['pre_session_notes'] ?? null,
            availabilitySlotId: $validated['availability_slot_id'] ?? null,
        );
    }
}
