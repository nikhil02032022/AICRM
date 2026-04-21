<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Portal;

use App\Http\Controllers\Controller;
use App\Models\CRM\Application;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Portal\ErpBridgeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// BRD: CRM-SP-007 — Applicant-facing ERP portal transition endpoint
final class PortalErpBridgeController extends Controller
{
    public function __construct(private readonly ErpBridgeService $bridgeService) {}

    /**
     * Issue an ERP bridge token and redirect to the ERP portal.
     * GET /portal/applications/{applicationUuid}/erp-transition
     */
    public function redirect(Request $request, string $applicationUuid): RedirectResponse
    {
        [$lead, $institution] = $this->resolveSession($request);

        $application = Application::withoutGlobalScopes()
            ->where('uuid', $applicationUuid)
            ->where('institution_id', $institution->id)
            ->first();

        if ($application === null || $application->lead_uuid !== $lead->uuid) {
            return redirect()->route('portal.applications.index')
                ->with('error', 'Application not found.');
        }

        if (! $this->bridgeService->isEnabled()) {
            return redirect()->route('portal.applications.show', $applicationUuid)
                ->with('info', 'ERP portal integration is not yet active for your institution. Please contact your admissions office.');
        }

        try {
            $plainToken  = $this->bridgeService->issue($lead, $application, $institution);
            $redirectUrl = $this->bridgeService->buildRedirectUrl($plainToken, $lead, $institution);
        } catch (AuthorizationException) {
            return redirect()->route('portal.applications.index')
                ->with('error', 'Application not found.');
        } catch (\RuntimeException $e) {
            return redirect()->route('portal.applications.show', $applicationUuid)
                ->with('error', $e->getMessage());
        }

        return redirect()->away($redirectUrl);
    }

    /** @return array{0: Lead, 1: Institution} */
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
