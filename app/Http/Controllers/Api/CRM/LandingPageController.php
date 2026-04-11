<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\DTOs\CRM\CreateLandingPageDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreLandingPageRequest;
use App\Http\Requests\Api\CRM\UpdateLandingPageRequest;
use App\Http\Resources\CRM\LandingPageResource;
use App\Models\CRM\LandingPage;
use App\Repositories\CRM\Marketing\LandingPageRepositoryInterface;
use App\Services\CRM\Marketing\LandingPageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-LC-005 — API controller for external consumers of landing pages
final class LandingPageController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LandingPageService $service,
        private readonly LandingPageRepositoryInterface $repository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('crm.campaigns.manage');

        $landingPages = $this->repository->paginate(
            filters: $request->only(['search', 'status', 'web_form_id']),
            perPage: (int) $request->get('per_page', 20),
        );

        return LandingPageResource::collection($landingPages);
    }

    public function store(StoreLandingPageRequest $request): JsonResponse
    {
        $user = $request->user();
        $dto = CreateLandingPageDTO::fromRequest($request->validated());

        $landingPage = $this->service->create($dto, (int) $user->institution_id, (int) $user->id);

        return $this->success(
            new LandingPageResource($landingPage->fresh(['webForm', 'creator'])),
            'Landing page created successfully.',
            201,
        );
    }

    public function show(LandingPage $landingPage): LandingPageResource
    {
        Gate::authorize('crm.campaigns.manage');

        return new LandingPageResource($landingPage->loadMissing(['webForm', 'creator']));
    }

    public function update(UpdateLandingPageRequest $request, LandingPage $landingPage): JsonResponse
    {
        $updated = $this->service->update($landingPage, $request->validated(), (int) $request->user()->institution_id);

        return $this->success(
            new LandingPageResource($updated),
            'Landing page updated successfully.',
        );
    }

    public function destroy(LandingPage $landingPage): JsonResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $this->service->delete($landingPage);

        return $this->success(null, 'Landing page deleted successfully.');
    }
}