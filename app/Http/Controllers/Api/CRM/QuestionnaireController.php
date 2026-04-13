<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\DTOs\CRM\CreateQualificationQuestionnaireDTO;
use App\DTOs\CRM\UpsertQuestionnaireResponseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreQualificationQuestionnaireRequest;
use App\Http\Requests\Api\CRM\UpdateQualificationQuestionnaireRequest;
use App\Http\Requests\Api\CRM\UpsertQuestionnaireResponseRequest;
use App\Http\Resources\CRM\QualificationQuestionnaireResource;
use App\Http\Resources\CRM\QuestionnaireResponseResource;
use App\Models\CRM\Lead;
use App\Models\CRM\QualificationQuestionnaire;
use App\Services\CRM\AI\QuestionnaireService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-LQ-009 — API endpoints for questionnaire management and response capture
final class QuestionnaireController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly QuestionnaireService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('crm.questionnaires.manage');

        $questionnaires = $this->service->paginate(
            filters: $request->only(['status', 'search']),
            perPage: (int) $request->input('per_page', 20),
        );

        return QualificationQuestionnaireResource::collection($questionnaires);
    }

    public function store(StoreQualificationQuestionnaireRequest $request): JsonResponse
    {
        $dto = CreateQualificationQuestionnaireDTO::fromRequest($request->validated());

        $questionnaire = $this->service->create(
            dto: $dto,
            institutionId: (int) $request->user()->institution_id,
            createdBy: (int) $request->user()->id,
        );

        return $this->created(
            new QualificationQuestionnaireResource($questionnaire),
            'Qualification questionnaire created successfully.',
        );
    }

    public function show(QualificationQuestionnaire $questionnaire): QualificationQuestionnaireResource
    {
        Gate::authorize('crm.questionnaires.manage');

        return new QualificationQuestionnaireResource($questionnaire);
    }

    public function update(
        UpdateQualificationQuestionnaireRequest $request,
        QualificationQuestionnaire $questionnaire,
    ): JsonResponse {
        $dto = CreateQualificationQuestionnaireDTO::fromRequest($request->validated());
        $updated = $this->service->update($questionnaire, $dto);

        return $this->success(
            new QualificationQuestionnaireResource($updated),
            'Qualification questionnaire updated successfully.',
        );
    }

    public function destroy(QualificationQuestionnaire $questionnaire): JsonResponse
    {
        Gate::authorize('crm.questionnaires.manage');

        $this->service->delete($questionnaire);

        return $this->success(null, 'Qualification questionnaire archived successfully.');
    }

    public function upsertResponse(
        UpsertQuestionnaireResponseRequest $request,
        QualificationQuestionnaire $questionnaire,
        Lead $lead,
    ): JsonResponse {
        $dto = UpsertQuestionnaireResponseDTO::fromRequest($request->validated());

        $response = $this->service->submitResponse(
            questionnaire: $questionnaire,
            lead: $lead,
            dto: $dto,
            institutionId: (int) $request->user()->institution_id,
            submittedBy: (int) $request->user()->id,
        );

        return $this->success(
            new QuestionnaireResponseResource($response->loadMissing(['questionnaire', 'lead'])),
            'Questionnaire response saved successfully.',
        );
    }
}
