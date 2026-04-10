<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\CounsellingSessionStatus;

// BRD: CRM-EC-015 — Update session outcome (complete/cancel/no-show)
final readonly class UpdateSessionDTO
{
    public function __construct(
        public readonly CounsellingSessionStatus $status,
        public readonly ?string $postSessionNotes = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            status: CounsellingSessionStatus::from($validated['status']),
            postSessionNotes: $validated['post_session_notes'] ?? null,
        );
    }
}
