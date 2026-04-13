<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\QuestionnaireStatus;

// BRD: CRM-LQ-009 — Typed payload for questionnaire create/update
final readonly class CreateQualificationQuestionnaireDTO
{
    /**
     * @param array<int, array<string, mixed>> $questions
     */
    public function __construct(
        public string $name,
        public QuestionnaireStatus $status,
        public array $questions,
        public ?int $campusId,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        return new self(
            name: (string) $validated['name'],
            status: QuestionnaireStatus::from((string) ($validated['status'] ?? QuestionnaireStatus::DRAFT->value)),
            questions: $validated['questions'],
            campusId: isset($validated['campus_id']) ? (int) $validated['campus_id'] : null,
        );
    }
}
