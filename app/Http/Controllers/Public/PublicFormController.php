<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PublicFormSubmissionRequest;
use App\Repositories\CRM\WebForm\WebFormRepositoryInterface;
use App\Services\CRM\WebForm\WebFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

// BRD: CRM-LC-001 — Unauthenticated controller for public web enquiry form rendering + submission
// BRD: CRM-LC-002 — Conditional logic rendered by Alpine.js on the public form (server provides schema)
// BRD: CRM-LC-009 — QR code lands here — slug resolves to the correct form
// BRD: CRM-LC-015 — UTM params captured by Alpine.js on x-init
// NO auth middleware on this controller — truly public
final class PublicFormController extends Controller
{
    public function __construct(
        private readonly WebFormService $service,
        private readonly WebFormRepositoryInterface $repository,
    ) {}

    /**
     * BRD: CRM-LC-001 — Render the public enquiry form.
     */
    public function show(string $slug): View|Response
    {
        $form = $this->repository->findActiveBySlug($slug);

        if ($form === null) {
            abort(404, 'This enquiry form is no longer available.');
        }

        return view('public.form.show', compact('form'));
    }

    /**
     * BRD: CRM-LC-001 — iFrame-safe bare render of the form (no nav/header).
     */
    public function embed(string $slug): View|Response
    {
        $form = $this->repository->findActiveBySlug($slug);

        if ($form === null) {
            abort(404);
        }

        return view('public.form.embed', compact('form'));
    }

    /**
     * BRD: CRM-LC-001 — Process the public form submission.
     * BRD: CRM-CR-001 — consent_given=accepted enforced in PublicFormSubmissionRequest
     * BRD: CRM-CR-002 — consent_ip captured from $request->ip()
     * BRD: CRM-LC-015 — source_utm_params passed from validated request data
     */
    public function submit(
        PublicFormSubmissionRequest $request,
        string $slug,
    ): RedirectResponse|JsonResponse {
        $form = $this->repository->findActiveBySlug($slug);

        if ($form === null) {
            abort(404, 'This enquiry form is no longer available.');
        }

        $lead = $this->service->handlePublicSubmission(
            form: $form,
            data: $request->validated(),
            ip: $request->ip() ?? '0.0.0.0',
        );

        // XHR / fetch request — return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your enquiry has been received. We will contact you shortly.',
                'data' => ['lead_uuid' => $lead->uuid],
            ]);
        }

        // Plain HTML form submission — redirect or show success
        if (!empty($form->redirect_url)) {
            return redirect()->away($form->redirect_url);
        }

        return redirect()
            ->route('public.form.show', $slug)
            ->with('submitted', true);
    }
}
