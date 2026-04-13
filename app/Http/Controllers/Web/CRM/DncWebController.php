<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreDncEntryRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Communication\DncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-TC-009 — Do-Not-Call (DNC) list management (web)
final class DncWebController extends Controller
{
    public function __construct(
        private readonly DncService $dncService,
    ) {}

    /**
     * BRD: CRM-TC-009 — Show the institution-scoped DNC list with optional keyword search.
     */
    public function index(Request $request): View
    {
        $this->authorize('crm.dnc.manage');

        $search = trim((string) $request->query('search', ''));
        $institutionId = (int) $request->user()->institution_id;

        $dncLeads = $this->dncService->paginateDncLeads($institutionId, $search);

        return view('crm.communication.voice.dnc.index', compact('dncLeads', 'search'));
    }

    /**
     * BRD: CRM-TC-009 — Add a lead to the DNC list with a mandatory reason.
     * Called from the lead show page or the DNC management screen.
     */
    public function store(StoreDncEntryRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('crm.dnc.manage');

        $this->dncService->addToDnc($lead, $request->validated()['reason']);

        return back()->with('success', "{$lead->fullName()} has been added to the DNC list.");
    }

    /**
     * BRD: CRM-TC-009 — Remove a lead from the DNC list (admin reinstatement).
     * DPDP: opt_out flag is preserved; only dnc_at/dnc_reason are cleared.
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('crm.dnc.manage');

        $this->dncService->removeFromDnc($lead);

        return back()->with('success', "{$lead->fullName()} has been removed from the DNC list. Confirm re-consent before resuming communication.");
    }
}
