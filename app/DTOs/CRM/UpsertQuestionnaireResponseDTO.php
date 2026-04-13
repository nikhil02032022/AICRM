<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-LQ-009 — Typed payload for questionnaire response submission
final readonly class UpsertQuestionnaireResponseDTO
{
    /**
     * @param array<string, mixed> $responses
     */
    public function __construct(
        public array $responses,
        public ?string $completedAt,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        return new self(
            responses: $validated['responses'],
            completedAt: $validated['completed_at'] ?? null,
        );
    }
}
