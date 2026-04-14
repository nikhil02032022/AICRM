<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\InitiateDigiLockerRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Integration\DigiLockerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-DM-006 — DigiLocker integration web controller (session auth, Blade views)
final class DigiLockerWebController extends Controller
{
    public function __construct(
        private readonly DigiLockerService $service
    ) {}

    /**
     * BRD: CRM-DM-006 — List DigiLocker documents for the institution
     */
    public function index(Request $request): View
    {
        $user      = $request->user();
        $documents = $this->service->list($user->institution_id);
        $leads     = Lead::query()
            ->where('institution_id', $user->institution_id)
            ->select(['uuid', 'first_name', 'last_name', 'mobile'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(200)
            ->get();

        return view('crm.integrations.digilocker', compact('documents', 'leads'));
    }

    /**
     * BRD: CRM-DM-006 — Initiate DigiLocker document request for a lead
     */
    public function store(InitiateDigiLockerRequest $request): RedirectResponse
    {
        $user      = $request->user();
        $lead      = Lead::where('uuid', $request->validated('lead_uuid'))
            ->where('institution_id', $user->institution_id)
            ->firstOrFail();

        $this->service->initiateRequest(
            $lead,
            $request->validated('document_type'),
            (int) $request->validated('consent_record_id'),
        );

        return back()->with('success', 'DigiLocker document request initiated.');
    }
}
