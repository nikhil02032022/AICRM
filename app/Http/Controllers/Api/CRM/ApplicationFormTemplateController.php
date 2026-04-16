<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\DTOs\CRM\CreateApplicationFormTemplateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreApplicationFormTemplateRequest;
use App\Http\Requests\Api\CRM\UpdateApplicationFormTemplateRequest;
use App\Http\Resources\CRM\ApplicationFormTemplateResource;
use App\Models\CRM\ApplicationFormTemplate;
use App\Models\User;
use App\Services\CRM\Application\ApplicationFormBuilderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-AP-001 — API controller for configurable multi-step application form templates
// Consumers: React Native app, A2A ERP integrations, approved third-party services
final class ApplicationFormTemplateController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ApplicationFormBuilderService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('crm.applications.view');

        $perPage = (int) $request->get('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $templates = $this->service->list(
            filters: $request->only(['is_active', 'search']),
            perPage: $perPage,
        );

        return $this->success(
            data: ApplicationFormTemplateResource::collection($templates),
            message: 'Application form templates retrieved successfully.',
            meta: [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        );
    }

    public function store(StoreApplicationFormTemplateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->institution_id === null) {
            return $this->forbidden('User is not linked to an institution.');
        }

        $validated = $request->validated();

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->service->generateUniqueSlug($validated['name'], $user->institution_id);
        }

        $dto = CreateApplicationFormTemplateDTO::fromRequest($validated);
        $template = $this->service->create($dto, $user->institution_id, $user->id);

        return $this->created(
            data: new ApplicationFormTemplateResource($template),
            message: 'Application form template created successfully.',
        );
    }

    public function show(ApplicationFormTemplate $applicationFormTemplate): JsonResponse
    {
        Gate::authorize('crm.applications.view', $applicationFormTemplate);

        return $this->success(
            data: new ApplicationFormTemplateResource($applicationFormTemplate),
            message: 'Application form template retrieved successfully.',
        );
    }

    public function update(UpdateApplicationFormTemplateRequest $request, ApplicationFormTemplate $applicationFormTemplate): JsonResponse
    {
        Gate::authorize('crm.applications.edit', $applicationFormTemplate);

        $validated = $request->validated();

        if (isset($validated['sections'])) {
            $validated['sections'] = CreateApplicationFormTemplateDTO::fromRequest([
                'name' => $applicationFormTemplate->name,
                'slug' => $applicationFormTemplate->slug,
                'sections' => $validated['sections'],
                'progression_rules' => $validated['progression_rules'] ?? $applicationFormTemplate->progression_rules,
                'settings' => $validated['settings'] ?? $applicationFormTemplate->settings,
                'minimum_completeness_percentage' => $validated['minimum_completeness_percentage'] ?? $applicationFormTemplate->minimum_completeness_percentage,
                'is_active' => $validated['is_active'] ?? $applicationFormTemplate->is_active,
                'campus_id' => $validated['campus_id'] ?? $applicationFormTemplate->campus_id,
            ])->sections;
        }

        $updated = $this->service->update($applicationFormTemplate, $validated);

        return $this->success(
            data: new ApplicationFormTemplateResource($updated),
            message: 'Application form template updated successfully.',
        );
    }

    public function destroy(ApplicationFormTemplate $applicationFormTemplate): JsonResponse
    {
        Gate::authorize('crm.applications.delete', $applicationFormTemplate);

        $this->service->delete($applicationFormTemplate);

        return $this->success(null, 'Application form template archived successfully.');
    }
}
