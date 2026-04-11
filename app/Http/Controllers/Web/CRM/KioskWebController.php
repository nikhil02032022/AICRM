<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Services\CRM\Marketing\KioskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-LC-013 — CRM web controller for walk-in kiosk launch and monitoring
final class KioskWebController extends Controller
{
    public function __construct(
        private readonly KioskService $service,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.campaigns.manage');

        $institution = $request->user()->institution;

        return view('crm.marketing.kiosk.index', [
            'institution' => $institution,
            'kioskUrl' => route('public.kiosk.show', ['institution' => $institution->uuid]),
            'submitUrl' => route('public.kiosk.submit', ['institution' => $institution->uuid]),
            'kioskLeads' => $this->service->recentLeads((int) $institution->id, 20),
        ]);
    }
}
