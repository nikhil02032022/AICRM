<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PayPublicApplicationDraftFeeRequest;
use App\Http\Requests\Public\SavePublicApplicationDraftRequest;
use App\Http\Requests\Public\SubmitPublicApplicationDraftRequest;
use App\Models\CRM\ApplicationFormDraft;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\ApplicationFormTemplate;
use App\Services\CRM\Application\ApplicationFormDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// BRD: CRM-AP-003 — Public controller for applicant save-and-resume form flow
final class PublicApplicationFormController extends Controller
{
    public function __construct(
        private readonly ApplicationFormDraftService $service,
    ) {}

    public function show(string $slug): View|Response
    {
        $template = $this->findActiveTemplateBySlug($slug);

        if ($template === null) {
            abort(404, 'This application form is not available.');
        }

        return view('public.application.fill', [
            'template' => $template,
            'draft' => null,
            'availableProgrammes' => $this->availableProgrammes((int) $template->institution_id),
        ]);
    }

    public function save(string $slug, SavePublicApplicationDraftRequest $request): RedirectResponse
    {
        $template = $this->findActiveTemplateBySlug($slug);

        if ($template === null) {
            abort(404, 'This application form is not available.');
        }

        try {
            $draft = $this->service->createDraft(
                template: $template,
                institutionId: $template->institution_id,
                createdBy: null,
                validated: $request->validated(),
            );

            return redirect()->route('public.application.resume', $draft->resume_token)
                ->with('success', 'Application progress saved. Use this page to continue later.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }

    public function resume(string $resumeToken): View|RedirectResponse
    {
        $draft = ApplicationFormDraft::withoutGlobalScopes()
            ->with('template')
            ->where('resume_token', $resumeToken)
            ->first();

        if ($draft === null) {
            abort(404, 'Resume link not found.');
        }

        try {
            $resolved = $this->service->resumeDraft($resumeToken, (int) $draft->institution_id);

            return view('public.application.fill', [
                'template' => $resolved->template,
                'draft' => $resolved,
                'availableProgrammes' => $this->availableProgrammes((int) $resolved->institution_id),
            ]);
        } catch (ValidationException $e) {
            return redirect()->route('public.application.show', $draft->template->slug)
                ->withErrors($e->errors());
        }
    }

    public function saveExisting(
        string $resumeToken,
        SavePublicApplicationDraftRequest $request,
    ): RedirectResponse {
        $draft = ApplicationFormDraft::withoutGlobalScopes()
            ->where('resume_token', $resumeToken)
            ->first();

        if ($draft === null) {
            abort(404, 'Resume link not found.');
        }

        try {
            $this->service->saveDraft($draft, $request->validated());

            return redirect()->route('public.application.resume', $resumeToken)
                ->with('success', 'Application progress saved.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }

    public function submit(
        string $resumeToken,
        SubmitPublicApplicationDraftRequest $request,
    ): RedirectResponse {
        $draft = ApplicationFormDraft::withoutGlobalScopes()
            ->where('resume_token', $resumeToken)
            ->first();

        if ($draft === null) {
            abort(404, 'Resume link not found.');
        }

        try {
            $this->service->submitDraft($draft, $request->validated());

            return redirect()->route('public.application.resume', $resumeToken)
                ->with('success', 'Application submitted successfully.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }

    public function payFee(
        string $resumeToken,
        PayPublicApplicationDraftFeeRequest $request,
    ): RedirectResponse {
        $draft = ApplicationFormDraft::withoutGlobalScopes()
            ->where('resume_token', $resumeToken)
            ->first();

        if ($draft === null) {
            abort(404, 'Resume link not found.');
        }

        try {
            $this->service->payApplicationFee($draft, $request->validated());

            return redirect()->route('public.application.resume', $resumeToken)
                ->with('success', 'Application fee paid successfully. You can now submit your application.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }

    private function findActiveTemplateBySlug(string $slug): ?ApplicationFormTemplate
    {
        $template = ApplicationFormTemplate::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return null;
        }

        $settings = $template->settings ?? [];

        if (! (bool) ($settings['allow_save_and_resume'] ?? false)) {
            return null;
        }

        if (! (bool) ($settings['mobile_optimised'] ?? true)) {
            return null;
        }

        return $template;
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
