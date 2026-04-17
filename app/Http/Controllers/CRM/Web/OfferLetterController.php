<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web;

use App\Http\Requests\CRM\OfferLetter\GenerateOfferLetterRequest;
use App\Http\Requests\CRM\OfferLetter\RecordOfferAcceptanceRequest;
use App\Models\CRM\Application;
use App\Models\CRM\OfferLetter;
use App\Models\CRM\OfferLetterTemplate;
use App\Repositories\CRM\Application\OfferLetterTemplateRepositoryInterface;
use App\Services\CRM\Application\OfferLetterService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-AP-012, CRM-AP-013, CRM-AP-015 — Offer letter web operations
final class OfferLetterController
{
    public function __construct(
        private readonly OfferLetterService $offerLetterService,
        private readonly OfferLetterTemplateRepositoryInterface $templateRepository,
    ) {}

    /**
     * List offer letters for an application.
     * GET /crm/applications/{application:uuid}/offers
     */
    public function index(Application $application): View
    {
        Gate::authorize('view', $application);

        $offers = $application->offerLetters()
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('crm.offer_letters.index', [
            'application' => $application,
            'offers' => $offers,
        ]);
    }

    /**
     * Show offer letter detail view with download option.
     * GET /crm/offers/{offer:uuid}
     */
    public function show(OfferLetter $offer): View
    {
        Gate::authorize('view', $offer);

        return view('crm.offer_letters.show', [
            'offer' => $offer,
            'application' => $offer->application,
            'lead' => $offer->lead,
        ]);
    }

    /**
     * Show form to generate new offer letter.
     * GET /crm/applications/{application:uuid}/offers/create
     */
    public function create(Application $application): View
    {
        Gate::authorize('create', [OfferLetter::class, $application]);

        $templates = $this->templateRepository->paginateActive(50);

        return view('crm.offer_letters.create', [
            'application' => $application,
            'templates' => $templates,
            'defaultExpiryDays' => 30,
        ]);
    }

    /**
     * Store (generate) a new offer letter.
     * POST /crm/applications/{application:uuid}/offers
     *
     * BRD: CRM-AP-012
     */
    public function store(
        Application $application,
        GenerateOfferLetterRequest $request,
    ): RedirectResponse {
        Gate::authorize('create', [OfferLetter::class, $application]);

        try {
            $offerLetter = $this->offerLetterService->issue(
                application: $application,
                programmeUuid: $application->programme_uuid,
                expiresAt: $request->getExpiryDate(),
                reason: $request->input('reason'),
                extraFields: [
                    'conditional' => $request->isConditional(),
                    'required_documents' => $request->getRequiredDocuments(),
                ]
            );

            return redirect()
                ->route('crm.offer_letters.show', $offerLetter->uuid)
                ->with('success', 'Offer letter generated successfully. PDF will be ready shortly.');

        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show form to accept an offer letter.
     * GET /crm/offers/{offer:uuid}/accept
     */
    public function acceptForm(OfferLetter $offer): View
    {
        Gate::authorize('update', $offer);

        if (! $offer->isValidForAcceptance()) {
            abort(422, 'This offer cannot be accepted (already accepted, declined, or expired).');
        }

        return view('crm.offer_letters.accept-form', [
            'offer' => $offer,
            'application' => $offer->application,
        ]);
    }

    /**
     * Record acceptance of an offer letter.
     * POST /crm/offers/{offer:uuid}/accept
     *
     * BRD: CRM-AP-015
     */
    public function accept(
        OfferLetter $offer,
        RecordOfferAcceptanceRequest $request,
    ): RedirectResponse {
        Gate::authorize('update', $offer);

        try {
            $this->offerLetterService->recordAcceptance(
                offerLetter: $offer,
                ipAddress: $request->ip(),
                notes: $request->input('notes'),
            );

            return redirect()
                ->route('crm.offer_letters.show', $offer->uuid)
                ->with('success', 'Offer accepted successfully! Your application will be processed.');

        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show form to decline an offer letter.
     * GET /crm/offers/{offer:uuid}/decline
     */
    public function declineForm(OfferLetter $offer): View
    {
        Gate::authorize('update', $offer);

        if ($offer->isDeclined()) {
            abort(422, 'This offer has already been declined.');
        }

        return view('crm.offer_letters.decline-form', [
            'offer' => $offer,
        ]);
    }

    /**
     * Record decline of an offer letter.
     * POST /crm/offers/{offer:uuid}/decline
     */
    public function decline(
        OfferLetter $offer,
        RecordOfferAcceptanceRequest $request,
    ): RedirectResponse {
        Gate::authorize('update', $offer);

        try {
            $this->offerLetterService->recordDecline(
                offerLetter: $offer,
                reason: $request->input('reason', 'Not specified'),
                ipAddress: $request->ip(),
            );

            return redirect()
                ->route('crm.offer_letters.show', $offer->uuid)
                ->with('success', 'Offer declined. Thank you for your time.');

        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Send offer letter via email/SMS/WhatsApp.
     * POST /crm/offers/{offer:uuid}/send
     *
     * BRD: CRM-AP-013
     */
    public function send(
        OfferLetter $offer,
        \Illuminate\Http\Request $request,
    ): RedirectResponse {
        Gate::authorize('send', $offer);

        try {
            $channel = $request->input('channel', 'email');

            if (! in_array($channel, ['email', 'sms', 'whatsapp'])) {
                throw new \InvalidArgumentException("Unsupported channel: {$channel}");
            }

            $this->offerLetterService->send(
                offerLetter: $offer,
                channel: $channel,
            );

            return redirect()
                ->route('crm.offer_letters.show', $offer->uuid)
                ->with('success', "Offer letter sent via " . strtoupper($channel));

        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Download offer letter PDF (redirect to signed URL).
     * GET /crm/offers/{offer:uuid}/download
     */
    public function download(OfferLetter $offer)
    {
        Gate::authorize('view', $offer);

        if (! $offer->pdf_path) {
            return back()->withErrors(['error' => 'PDF not yet generated. Please try again in a moment.']);
        }

        $downloadUrl = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl(
            $offer->pdf_path,
            now()->addMinutes(15),
        );

        return redirect()->away($downloadUrl);
    }

    /**
     * Mark a required document as verified on a conditional offer.
     * POST /crm/offers/{offer:uuid}/documents/{docType}/verify
     *
     * BRD: CRM-AP-014
     */
    public function verifyDocument(
        OfferLetter $offer,
        string $docType,
        \Illuminate\Http\Request $request,
    ): RedirectResponse {
        Gate::authorize('update', $offer);

        try {
            $verified = (bool) $request->input('verified', true);
            $this->offerLetterService->verifyDocument($offer, $docType, $verified);

            return redirect()
                ->route('crm.offer_letters.show', $offer->uuid)
                ->with('success', "Document '{$docType}' marked as " . ($verified ? 'verified' : 'unverified') . '.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate and return a public portal link for the student.
     * POST /crm/offers/{offer:uuid}/portal-link
     *
     * BRD: CRM-AP-015
     */
    public function generatePortalLink(
        OfferLetter $offer,
        \Illuminate\Http\Request $request,
    ): RedirectResponse {
        Gate::authorize('update', $offer);

        try {
            $expiryHours = (int) $request->input('expiry_hours', 72);
            $token = $this->offerLetterService->generateAcceptanceToken($offer, $expiryHours);
            $url = route('portal.offers.show', $token);

            return redirect()
                ->route('crm.offer_letters.show', $offer->uuid)
                ->with('success', "Portal link generated. Share this URL with the applicant: {$url}");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Manage offer letter templates.
     * GET /crm/offer-templates
     */
    public function manageTemplates(): View
    {
        Gate::authorize('manage-institution-settings');

        $templates = $this->templateRepository->paginateActive(15);

        return view('crm.offer_letters.manage-templates', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show form to edit offer letter template.
     * GET /crm/offer-templates/{template:uuid}/edit
     */
    public function editTemplate(OfferLetterTemplate $template): View
    {
        Gate::authorize('manage-institution-settings');

        return view('crm.offer_letters.edit-template', [
            'template' => $template,
            'mergeTags' => OfferLetterTemplate::getDefaultMergeTags(),
        ]);
    }

    /**
     * Update offer letter template.
     * PUT /crm/offer-templates/{template:uuid}
     */
    public function updateTemplate(
        OfferLetterTemplate $template,
        \Illuminate\Http\Request $request,
    ): RedirectResponse {
        Gate::authorize('manage-institution-settings');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'html_template' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()
            ->route('crm.offer_templates.manage')
            ->with('success', 'Template updated successfully.');
    }
}
