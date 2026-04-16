<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\PayApplicationFormDraftFeeRequest;
use App\Http\Requests\Api\CRM\SubmitApplicationFormDraftRequest;
use App\Http\Requests\Api\CRM\StoreApplicationFormDraftRequest;
use App\Http\Requests\Api\CRM\UpdateApplicationFormDraftRequest;
use App\Http\Resources\CRM\ApplicationFormDraftResource;
use App\Models\CRM\ApplicationFormDraft;
use App\Models\CRM\ApplicationFormTemplate;
use App\Models\User;
use App\Services\CRM\Application\ApplicationFormDraftService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-AP-003 — API controller for application save-and-resume draft lifecycle
final class ApplicationFormDraftController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ApplicationFormDraftService $service,
    ) {}

    public function store(StoreApplicationFormDraftRequest $request, ApplicationFormTemplate $applicationFormTemplate): JsonResponse
    {
        Gate::authorize('crm.applications.create');

        /** @var User $user */
        $user = $request->user();

        if ($user->institution_id === null) {
            return $this->forbidden('User is not linked to an institution.');
        }

        $draft = $this->service->createDraft(
            template: $applicationFormTemplate,
            institutionId: $user->institution_id,
            createdBy: $user->id,
            validated: $request->validated(),
        );

        return $this->created(
            data: new ApplicationFormDraftResource($draft->load('template:id,uuid')),
            message: 'Application form draft created successfully.',
        );
    }

    public function show(ApplicationFormDraft $applicationFormDraft): JsonResponse
    {
        Gate::authorize('view', $applicationFormDraft);

        return $this->success(
            data: new ApplicationFormDraftResource($applicationFormDraft->load('template:id,uuid')),
            message: 'Application form draft retrieved successfully.',
        );
    }

    public function update(UpdateApplicationFormDraftRequest $request, ApplicationFormDraft $applicationFormDraft): JsonResponse
    {
        Gate::authorize('edit', $applicationFormDraft);

        $updated = $this->service->saveDraft($applicationFormDraft, $request->validated());

        return $this->success(
            data: new ApplicationFormDraftResource($updated->load('template:id,uuid')),
            message: 'Application form draft saved successfully.',
        );
    }

    public function submit(SubmitApplicationFormDraftRequest $request, ApplicationFormDraft $applicationFormDraft): JsonResponse
    {
        Gate::authorize('edit', $applicationFormDraft);

        $submitted = $this->service->submitDraft($applicationFormDraft, $request->validated());

        return $this->success(
            data: new ApplicationFormDraftResource($submitted->load('template:id,uuid')),
            message: 'Application form draft submitted successfully.',
        );
    }

    public function payFee(
        PayApplicationFormDraftFeeRequest $request,
        ApplicationFormDraft $applicationFormDraft,
    ): JsonResponse {
        Gate::authorize('edit', $applicationFormDraft);

        $paid = $this->service->payApplicationFee($applicationFormDraft, $request->validated());

        return $this->success(
            data: new ApplicationFormDraftResource($paid->load('template:id,uuid')),
            message: 'Application fee paid successfully.',
        );
    }

    public function resume(string $resumeToken, Request $request): JsonResponse
    {
        Gate::authorize('crm.applications.view');

        /** @var User $user */
        $user = $request->user();

        if ($user->institution_id === null) {
            return $this->forbidden('User is not linked to an institution.');
        }

        $draft = $this->service->resumeDraft($resumeToken, $user->institution_id);
        Gate::authorize('view', $draft);

        return $this->success(
            data: new ApplicationFormDraftResource($draft->load('template:id,uuid')),
            message: 'Application form draft resumed successfully.',
        );
    }
}
