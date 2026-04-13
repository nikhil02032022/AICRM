<?php

declare(strict_types=1);

namespace App\Repositories\CRM\AI;

use App\DTOs\CRM\CreateQualificationQuestionnaireDTO;
use App\DTOs\CRM\UpsertQuestionnaireResponseDTO;
use App\Models\CRM\Lead;
use App\Models\CRM\QualificationQuestionnaire;
use App\Models\CRM\QuestionnaireResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-LQ-009 — Contract for questionnaire persistence and response storage
interface QuestionnaireRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function create(CreateQualificationQuestionnaireDTO $dto, int $institutionId, int $createdBy): QualificationQuestionnaire;

    public function update(QualificationQuestionnaire $questionnaire, CreateQualificationQuestionnaireDTO $dto): QualificationQuestionnaire;

    public function softDelete(QualificationQuestionnaire $questionnaire): void;

    public function upsertResponse(
        QualificationQuestionnaire $questionnaire,
        Lead $lead,
        UpsertQuestionnaireResponseDTO $dto,
        int $institutionId,
        int $submittedBy,
    ): QuestionnaireResponse;
}
