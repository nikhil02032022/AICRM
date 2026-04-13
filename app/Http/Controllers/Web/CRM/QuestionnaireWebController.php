<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\CreateQualificationQuestionnaireDTO;
use App\DTOs\CRM\UpsertQuestionnaireResponseDTO;
use App\Enums\CRM\QuestionnaireStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\UpsertQuestionnaireResponseRequest;
use App\Models\CRM\Lead;
use App\Models\CRM\QualificationQuestionnaire;
use App\Services\CRM\AI\QuestionnaireService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// BRD: CRM-LQ-009 — Web controller for questionnaire management and lead responses
final class QuestionnaireWebController extends Controller
{
    public function __construct(
        private readonly QuestionnaireService $service,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.questionnaires.manage');

        $questionnaires = $this->service->paginate(
            filters: $request->only(['status', 'search']),
            perPage: 20,
        );

        return view('crm.ai.questionnaires.index', [
            'questionnaires' => $questionnaires,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.questionnaires.manage');

        return view('crm.ai.questionnaires.form', [
            'questionnaire' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('crm.questionnaires.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::enum(QuestionnaireStatus::class)],
            'campus_id' => ['nullable', 'integer', 'min:1'],
            'questions_json' => ['required', 'string'],
        ]);

        $decodedQuestions = json_decode((string) $validated['questions_json'], true);

        if (! is_array($decodedQuestions) || $decodedQuestions === []) {
            return back()
                ->withInput()
                ->withErrors(['questions' => 'Questions JSON must be a non-empty JSON array.']);
        }

        $validated['questions'] = $decodedQuestions;
        $dto = CreateQualificationQuestionnaireDTO::fromRequest($validated);

        $questionnaire = $this->service->create(
            dto: $dto,
            institutionId: (int) $request->user()->institution_id,
            createdBy: (int) $request->user()->id,
        );

        return redirect()
            ->route('crm.scoring.questionnaires.edit', $questionnaire->uuid)
            ->with('success', 'Qualification questionnaire created successfully.');
    }

    public function edit(QualificationQuestionnaire $questionnaire): View
    {
        Gate::authorize('crm.questionnaires.manage');

        return view('crm.ai.questionnaires.form', [
            'questionnaire' => $questionnaire,
        ]);
    }

    public function update(Request $request, QualificationQuestionnaire $questionnaire): RedirectResponse
    {
        Gate::authorize('crm.questionnaires.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::enum(QuestionnaireStatus::class)],
            'campus_id' => ['nullable', 'integer', 'min:1'],
            'questions_json' => ['required', 'string'],
        ]);

        $decodedQuestions = json_decode((string) $validated['questions_json'], true);

        if (! is_array($decodedQuestions) || $decodedQuestions === []) {
            return back()
                ->withInput()
                ->withErrors(['questions' => 'Questions JSON must be a non-empty JSON array.']);
        }

        $validated['questions'] = $decodedQuestions;
        $dto = CreateQualificationQuestionnaireDTO::fromRequest($validated);
        $this->service->update($questionnaire, $dto);

        return redirect()
            ->route('crm.scoring.questionnaires.edit', $questionnaire->uuid)
            ->with('success', 'Qualification questionnaire updated successfully.');
    }

    public function destroy(QualificationQuestionnaire $questionnaire): RedirectResponse
    {
        Gate::authorize('crm.questionnaires.manage');

        $this->service->delete($questionnaire);

        return redirect()
            ->route('crm.scoring.questionnaires.index')
            ->with('success', 'Qualification questionnaire archived successfully.');
    }

    public function storeResponse(
        UpsertQuestionnaireResponseRequest $request,
        QualificationQuestionnaire $questionnaire,
        Lead $lead,
    ): RedirectResponse {
        $dto = UpsertQuestionnaireResponseDTO::fromRequest($request->validated());

        $this->service->submitResponse(
            questionnaire: $questionnaire,
            lead: $lead,
            dto: $dto,
            institutionId: (int) $request->user()->institution_id,
            submittedBy: (int) $request->user()->id,
        );

        return redirect()
            ->route('crm.leads.show', $lead->uuid)
            ->with('success', 'Qualification questionnaire response saved successfully.');
    }
}
