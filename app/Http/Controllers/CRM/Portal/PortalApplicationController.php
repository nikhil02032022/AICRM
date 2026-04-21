<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Portal;

use App\Http\Controllers\Controller;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Portal\PortalApplicationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SP-006 — Applicant-facing list and detail views for multiple simultaneous applications
final class PortalApplicationController extends Controller
{
    public function __construct(private readonly PortalApplicationService $applicationService) {}

    /**
     * List all applications for the authenticated applicant.
     * GET /portal/applications
     */
    public function index(Request $request): View
    {
        [$lead, $institution] = $this->resolveSession($request);

        $applications = $this->applicationService->list($lead, $institution);

        return view('portal.applications.index', [
            'applications' => $applications,
            'applicant'    => $lead,
        ]);
    }

    /**
     * Show a single application detail for the authenticated applicant.
     * GET /portal/applications/{applicationUuid}
     */
    public function show(Request $request, string $applicationUuid): View|RedirectResponse
    {
        [$lead, $institution] = $this->resolveSession($request);

        try {
            $data = $this->applicationService->detail($applicationUuid, $lead, $institution);
        } catch (AuthorizationException) {
            return redirect()->route('portal.applications.index')
                ->with('error', 'Application not found.');
        }

        return view('portal.applications.show', array_merge($data, ['applicant' => $lead]));
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
