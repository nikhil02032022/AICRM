<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Portal;

use App\Http\Controllers\Controller;
use App\Services\CRM\Application\OfferLetterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AP-015 — Public student portal for offer acceptance (no auth required)
final class OfferLetterPortalController extends Controller
{
    public function __construct(
        private readonly OfferLetterService $offerLetterService,
    ) {}

    /**
     * Show the offer letter to the applicant via a public token link.
     * GET /portal/offers/{token}
     */
    public function show(string $token): View|RedirectResponse
    {
        $offer = $this->offerLetterService->resolveByToken($token);

        if (! $offer) {
            abort(410, 'This offer link has expired or is invalid.');
        }

        return view('portal.offers.show', [
            'offer' => $offer,
            'application' => $offer->application,
            'lead' => $offer->lead,
            'token' => $token,
            'canAccept' => $offer->isValidForAcceptance(),
        ]);
    }

    /**
     * Record applicant acceptance via public token link.
     * POST /portal/offers/{token}/accept
     *
     * BRD: CRM-AP-015, DPDP: captures IP + timestamp without login
     */
    public function accept(string $token, Request $request): RedirectResponse
    {
        $offer = $this->offerLetterService->resolveByToken($token);

        if (! $offer) {
            abort(410, 'This offer link has expired or is invalid.');
        }

        try {
            $this->offerLetterService->recordAcceptance(
                offerLetter: $offer,
                ipAddress: $request->ip(),
                notes: $request->input('notes'),
            );

            return redirect()
                ->route('portal.offers.show', $token)
                ->with('success', 'Your offer has been accepted. We will be in touch shortly.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Record applicant decline via public token link.
     * POST /portal/offers/{token}/decline
     */
    public function decline(string $token, Request $request): RedirectResponse
    {
        $offer = $this->offerLetterService->resolveByToken($token);

        if (! $offer) {
            abort(410, 'This offer link has expired or is invalid.');
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->offerLetterService->recordDecline(
                offerLetter: $offer,
                reason: $request->input('reason'),
                ipAddress: $request->ip(),
            );

            return redirect()
                ->route('portal.offers.show', $token)
                ->with('info', 'You have declined this offer. Thank you for letting us know.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
