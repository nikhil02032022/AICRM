<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\PayApplicationFormDraftFeeRequest;
use App\Http\Requests\Api\CRM\StoreApplicationFormDraftRequest;
use App\Http\Requests\Api\CRM\SubmitApplicationFormDraftRequest;
use App\Http\Requests\Api\CRM\UpdateApplicationFormDraftRequest;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\ApplicationFormDraft;
use App\Models\CRM\ApplicationFormTemplate;
use App\Models\User;
use App\Services\CRM\Application\ApplicationFormDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// BRD: CRM-AP-003 — Web controller for authenticated application draft fill/save/resume/submit flow
final class ApplicationFormDraftWebController extends Controller
{
    public function __construct(
        private readonly ApplicationFormDraftService $service,
    ) {}

    public function fillTemplate(ApplicationFormTemplate $applicationFormTemplate): View|RedirectResponse
    {
        Gate::authorize('crm.applications.create');

        if (! $this->isMobileOptimisedEnabled($applicationFormTemplate)) {
            return redirect()->route('crm.applications.forms.index')
                ->with('error', 'This template is not mobile-optimised. Enable AP-006 mobile optimisation in template settings.');
        }

        if (! $this->isSaveAndResumeEnabled($applicationFormTemplate)) {
            return redirect()->route('crm.applications.forms.index')
                ->with('error', 'Save and resume is disabled for this template. Enable AP-003 in template settings first.');
        }

        return view('crm.applications.forms.fill', [
            'template' => $applicationFormTemplate,
            'draft' => null,
            'availableProgrammes' => $this->availableProgrammes((int) $applicationFormTemplate->institution_id),
        ]);
    }

    public function saveTemplateDraft(
        StoreApplicationFormDraftRequest $request,
        ApplicationFormTemplate $applicationFormTemplate,
    ): RedirectResponse {
        Gate::authorize('crm.applications.create');

        /** @var User $user */
        $user = $request->user();

        if ($user->institution_id === null) {
            return back()->withInput()->with('error', 'Your account is not linked to an institution.');
        }

        try {
            $draft = $this->service->createDraft(
                template: $applicationFormTemplate,
                institutionId: $user->institution_id,
                createdBy: $user->id,
                validated: $request->validated(),
            );

            return redirect()->route('crm.applications.drafts.resume', $draft->uuid)
                ->with('success', 'Draft created successfully. You can continue filling and save progress anytime.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }

    public function resume(ApplicationFormDraft $applicationFormDraft): View
    {
        Gate::authorize('view', $applicationFormDraft);

        abort_unless($this->isMobileOptimisedEnabled($applicationFormDraft->template), 404);

        return view('crm.applications.forms.fill', [
            'template' => $applicationFormDraft->template,
            'draft' => $applicationFormDraft,
            'availableProgrammes' => $this->availableProgrammes((int) $applicationFormDraft->institution_id),
        ]);
    }

    public function save(
        UpdateApplicationFormDraftRequest $request,
        ApplicationFormDraft $applicationFormDraft,
    ): RedirectResponse {
        Gate::authorize('edit', $applicationFormDraft);

        try {
            $this->service->saveDraft($applicationFormDraft, $request->validated());

            return redirect()->route('crm.applications.drafts.resume', $applicationFormDraft->uuid)
                ->with('success', 'Draft saved successfully.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }

    public function submit(
        SubmitApplicationFormDraftRequest $request,
        ApplicationFormDraft $applicationFormDraft,
    ): RedirectResponse {
        Gate::authorize('edit', $applicationFormDraft);

        try {
            $this->service->submitDraft($applicationFormDraft, $request->validated());

            return redirect()->route('crm.applications.forms.index')
                ->with('success', 'Application draft submitted successfully.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }

    public function payFee(
        PayApplicationFormDraftFeeRequest $request,
        ApplicationFormDraft $applicationFormDraft,
    ): RedirectResponse {
        Gate::authorize('edit', $applicationFormDraft);

        try {
            $this->service->payApplicationFee($applicationFormDraft, $request->validated());

            return redirect()->route('crm.applications.drafts.resume', $applicationFormDraft->uuid)
                ->with('success', 'Application fee paid successfully. You can now submit this draft.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }

    private function isSaveAndResumeEnabled(ApplicationFormTemplate $template): bool
    {
        $settings = $template->settings ?? [];

        return (bool) ($settings['allow_save_and_resume'] ?? false);
    }

    private function isMobileOptimisedEnabled(ApplicationFormTemplate $template): bool
    {
        $settings = $template->settings ?? [];

        return (bool) ($settings['mobile_optimised'] ?? true);
    }

    private function availableProgrammes(int $institutionId)
    {
        return CrmProgramme::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->whereNotNull('erp_programme_uuid')
            ->orderBy('name')
            ->get(['name', 'code', 'erp_programme_uuid']);
    }
}
