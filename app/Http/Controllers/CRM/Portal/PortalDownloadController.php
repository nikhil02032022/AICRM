<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Portal;

use App\Http\Controllers\Controller;
use App\Models\CRM\Lead;
use App\Models\CRM\Institution;
use App\Services\CRM\Portal\PortalDownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// BRD: CRM-SP-005 — Applicant-facing PDF downloads (offer letter, admission confirmation, payment receipt)
final class PortalDownloadController extends Controller
{
    public function __construct(private readonly PortalDownloadService $downloadService) {}

    /**
     * Redirect to a signed S3 URL for the applicant's offer letter PDF.
     * GET /portal/downloads/{applicationUuid}/offer-letter
     */
    public function offerLetter(Request $request, string $applicationUuid): RedirectResponse
    {
        [$lead, $institution] = $this->resolveSession($request);

        try {
            $url = $this->downloadService->offerLetterUrl($lead, $applicationUuid, $institution);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    /**
     * Stream an admission confirmation letter PDF.
     * GET /portal/downloads/{applicationUuid}/admission-letter
     */
    public function admissionLetter(Request $request, string $applicationUuid): Response|RedirectResponse
    {
        [$lead, $institution] = $this->resolveSession($request);

        try {
            $pdf = $this->downloadService->admissionLetterPdf($lead, $applicationUuid, $institution);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="admission-confirmation.pdf"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    /**
     * Stream a payment receipt PDF.
     * GET /portal/downloads/receipts/{transactionUuid}
     */
    public function paymentReceipt(Request $request, string $transactionUuid): Response|RedirectResponse
    {
        [$lead, $institution] = $this->resolveSession($request);

        try {
            $pdf = $this->downloadService->paymentReceiptPdf($lead, $transactionUuid, $institution);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="payment-receipt.pdf"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    /**
     * @return array{0: Lead, 1: Institution}
     */
    private function resolveSession(Request $request): array
    {
        /** @var \App\Models\CRM\Portal\PortalSession $session */
        $session = $request->attributes->get('portal_session');

        /** @var Institution $institution */
        $institution = $request->attributes->get('portal_institution');

        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $session->lead_uuid)
            ->firstOrFail();

        return [$lead, $institution];
    }
}
