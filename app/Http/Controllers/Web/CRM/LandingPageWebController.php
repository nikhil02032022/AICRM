<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\CreateLandingPageDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreLandingPageRequest;
use App\Http\Requests\Api\CRM\UpdateLandingPageRequest;
use App\Models\CRM\LandingPage;
use App\Models\CRM\WebForm;
use App\Repositories\CRM\Marketing\LandingPageRepositoryInterface;
use App\Services\CRM\Marketing\LandingPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-LC-005 — Session-authenticated CRM controller for landing page management
final class LandingPageWebController extends Controller
{
    public function __construct(
        private readonly LandingPageService $service,
        private readonly LandingPageRepositoryInterface $repository,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.campaigns.manage');

        $landingPages = $this->repository->paginate(
            filters: $request->only(['search', 'status', 'web_form_id']),
            perPage: 20,
        );

        return view('crm.marketing.landing-pages.index', [
            'landingPages' => $landingPages,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.campaigns.manage');

        return view('crm.marketing.landing-pages.edit', [
            'landingPage' => null,
            'webForms' => $this->availableForms(),
        ]);
    }

    public function store(StoreLandingPageRequest $request): RedirectResponse
    {
        $user = $request->user();
        $landingPage = $this->service->create(
            CreateLandingPageDTO::fromRequest($request->validated()),
            (int) $user->institution_id,
            (int) $user->id,
        );

        return redirect()
            ->route('crm.marketing.landing-pages.edit', $landingPage->uuid)
            ->with('success', 'Landing page created successfully.');
    }

    public function edit(LandingPage $landingPage): View
    {
        Gate::authorize('crm.campaigns.manage');

        $landingPage->loadMissing(['webForm', 'creator'])->loadCount('landingPageViews');
        $landingPage->setAttribute(
            'view_count_last_7d',
            $landingPage->landingPageViews()->where('viewed_at', '>=', now()->subDays(7))->count(),
        );

        return view('crm.marketing.landing-pages.edit', [
            'landingPage' => $landingPage,
            'webForms' => $this->availableForms(),
        ]);
    }

    public function update(UpdateLandingPageRequest $request, LandingPage $landingPage): RedirectResponse
    {
        $updated = $this->service->update($landingPage, $request->validated(), (int) $request->user()->institution_id);

        return redirect()
            ->route('crm.marketing.landing-pages.edit', $updated->uuid)
            ->with('success', 'Landing page updated successfully.');
    }

    public function destroy(LandingPage $landingPage): RedirectResponse
    {
        Gate::authorize('crm.campaigns.manage');

        $this->service->delete($landingPage);

        return redirect()
            ->route('crm.marketing.landing-pages.index')
            ->with('success', 'Landing page deleted successfully.');
    }

    /** @return \Illuminate\Support\Collection<int, WebForm> */
    private function availableForms()
    {
        return WebForm::query()->orderBy('name')->get(['id', 'uuid', 'name', 'slug']);
    }
}