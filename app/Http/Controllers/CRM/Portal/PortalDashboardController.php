<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Portal;

use App\Http\Controllers\Controller;
use App\Models\CRM\Lead;
use App\Services\CRM\Portal\PortalDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SP-003 — Renders the applicant self-service dashboard
final class PortalDashboardController extends Controller
{
    public function __construct(private readonly PortalDashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\CRM\Portal\PortalSession $session */
        $session = $request->attributes->get('portal_session');

        /** @var \App\Models\CRM\Institution $institution */
        $institution = $request->attributes->get('portal_institution');

        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $session->lead_uuid)
            ->firstOrFail();

        $data = $this->dashboardService->getData($lead, $institution);

        return view('portal.dashboard', array_merge($data, ['applicant' => $lead]));
    }
}
