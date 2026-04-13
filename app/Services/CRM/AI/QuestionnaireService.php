<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\DTOs\CRM\CreateQualificationQuestionnaireDTO;
use App\DTOs\CRM\UpsertQuestionnaireResponseDTO;
use App\Models\CRM\Lead;
use App\Models\CRM\QualificationQuestionnaire;
use App\Models\CRM\QuestionnaireResponse;
use App\Repositories\CRM\AI\QuestionnaireRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-LQ-009 — Service orchestrating questionnaire lifecycle and responses
final class QuestionnaireService
{
    public function __construct(
        private readonly QuestionnaireRepositoryInterface $repository,
    ) {}

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function create(CreateQualificationQuestionnaireDTO $dto, int $institutionId, int $createdBy): QualificationQuestionnaire
    {
        return $this->repository->create($dto, $institutionId, $createdBy);
    }

    public function update(QualificationQuestionnaire $questionnaire, CreateQualificationQuestionnaireDTO $dto): QualificationQuestionnaire
    {
        return $this->repository->update($questionnaire, $dto);
    }

    public function delete(QualificationQuestionnaire $questionnaire): void
    {
        $this->repository->softDelete($questionnaire);
    }

    public function submitResponse(
        QualificationQuestionnaire $questionnaire,
        Lead $lead,
        UpsertQuestionnaireResponseDTO $dto,
        int $institutionId,
        int $submittedBy,
    ): QuestionnaireResponse {
        return $this->repository->upsertResponse($questionnaire, $lead, $dto, $institutionId, $submittedBy);
    }
}
