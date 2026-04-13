<?php

declare(strict_types=1);

namespace App\Repositories\CRM\AI;

use App\DTOs\CRM\CreateQualificationQuestionnaireDTO;
use App\DTOs\CRM\UpsertQuestionnaireResponseDTO;
use App\Models\CRM\Lead;
use App\Models\CRM\QualificationQuestionnaire;
use App\Models\CRM\QuestionnaireResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

// BRD: CRM-LQ-009 — Eloquent persistence for questionnaires and responses
final class EloquentQuestionnaireRepository implements QuestionnaireRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return QualificationQuestionnaire::query()
            ->when(($filters['status'] ?? null) !== null, function ($query) use ($filters): void {
                $query->where('status', (string) $filters['status']);
            })
            ->when(($filters['search'] ?? null) !== null, function ($query) use ($filters): void {
                $query->where('name', 'like', '%'.(string) $filters['search'].'%');
            })
            ->latest()
            ->paginate($perPage);
    }

    public function create(CreateQualificationQuestionnaireDTO $dto, int $institutionId, int $createdBy): QualificationQuestionnaire
    {
        return QualificationQuestionnaire::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institutionId,
            'campus_id' => $dto->campusId,
            'name' => $dto->name,
            'status' => $dto->status->value,
            'questions' => $dto->questions,
            'created_by' => $createdBy,
        ]);
    }

    public function update(QualificationQuestionnaire $questionnaire, CreateQualificationQuestionnaireDTO $dto): QualificationQuestionnaire
    {
        $questionnaire->update([
            'name' => $dto->name,
            'status' => $dto->status->value,
            'questions' => $dto->questions,
            'campus_id' => $dto->campusId,
        ]);

        return $questionnaire->fresh();
    }

    public function softDelete(QualificationQuestionnaire $questionnaire): void
    {
        $questionnaire->delete();
    }

    public function upsertResponse(
        QualificationQuestionnaire $questionnaire,
        Lead $lead,
        UpsertQuestionnaireResponseDTO $dto,
        int $institutionId,
        int $submittedBy,
    ): QuestionnaireResponse {
        $response = QuestionnaireResponse::withoutGlobalScopes()->firstOrNew([
            'qualification_questionnaire_id' => $questionnaire->id,
            'lead_id' => $lead->id,
        ]);

        if (! $response->exists) {
            $response->uuid = (string) Str::uuid();
            $response->institution_id = $institutionId;
            $response->campus_id = $lead->campus_id;
        }

        $response->submitted_by = $submittedBy;
        $response->responses = $dto->responses;
        $response->completed_at = $dto->completedAt ?? now()->toDateTimeString();
        $response->save();

        return $response->fresh();
    }
}
