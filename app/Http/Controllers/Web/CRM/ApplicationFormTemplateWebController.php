<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\CreateApplicationFormTemplateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreApplicationFormTemplateRequest;
use App\Http\Requests\Api\CRM\UpdateApplicationFormTemplateRequest;
use App\Models\CRM\ApplicationFormTemplate;
use App\Models\User;
use App\Services\CRM\Application\ApplicationFormBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-AP-001 — Web controller for configurable multi-step application form builder
final class ApplicationFormTemplateWebController extends Controller
{
    public function __construct(
        private readonly ApplicationFormBuilderService $service,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.applications.view');

        $templates = $this->service->list(
            filters: $request->only(['is_active', 'search']),
            perPage: 20,
        );

        return view('crm.applications.forms.index', compact('templates'));
    }

    public function create(): View
    {
        Gate::authorize('crm.applications.create');

        return view('crm.applications.forms.create');
    }

    public function store(StoreApplicationFormTemplateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->institution_id === null) {
            return back()->withInput()->with('error', 'Your account is not linked to an institution.');
        }

        $validated = $request->validated();

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->service->generateUniqueSlug($validated['name'], $user->institution_id);
        }

        $dto = CreateApplicationFormTemplateDTO::fromRequest($validated);
        $this->service->create($dto, $user->institution_id, $user->id);

        return redirect()->route('crm.applications.forms.index')
            ->with('success', 'Application form template created successfully.');
    }

    public function edit(ApplicationFormTemplate $applicationFormTemplate): View
    {
        Gate::authorize('crm.applications.edit');

        return view('crm.applications.forms.create', [
            'template' => $applicationFormTemplate,
        ]);
    }

    public function update(UpdateApplicationFormTemplateRequest $request, ApplicationFormTemplate $applicationFormTemplate): RedirectResponse
    {
        Gate::authorize('crm.applications.edit');

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

        $this->service->update($applicationFormTemplate, $validated);

        return redirect()->route('crm.applications.forms.index')
            ->with('success', 'Application form template updated successfully.');
    }

    public function destroy(ApplicationFormTemplate $applicationFormTemplate): RedirectResponse
    {
        Gate::authorize('crm.applications.delete');

        $this->service->delete($applicationFormTemplate);

        return redirect()->route('crm.applications.forms.index')
            ->with('success', 'Application form template archived successfully.');
    }
}
