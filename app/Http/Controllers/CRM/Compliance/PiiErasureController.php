<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Compliance;

use App\Http\Controllers\Controller;
use App\Models\CRM\Compliance\PiiErasureRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Compliance\PiiErasureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-CR-005 — Right-to-erasure: PII anonymised within 30 days
final class PiiErasureController extends Controller
{
    public function __construct(private readonly PiiErasureService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.compliance.erasure.view');

        $erasureRequests = PiiErasureRequest::where('institution_id', $request->user()->institution_id)
            ->with('lead:id,first_name,last_name,uuid')
            ->orderByDesc('requested_at')
            ->get();

        return view('crm.compliance.erasure.index', compact('erasureRequests'));
    }

    public function show(PiiErasureRequest $piiErasureRequest): View
    {
        $this->authorize('crm.compliance.erasure.view');

        return view('crm.compliance.erasure.show', ['erasureRequest' => $piiErasureRequest]);
    }

    public function schedule(Request $request, int $leadId): RedirectResponse
    {
        $this->authorize('crm.compliance.erasure.schedule');

        $lead = Lead::withoutGlobalScopes()
            ->where('institution_id', $request->user()->institution_id)
            ->findOrFail($leadId);

        $this->service->schedule($lead, $request->user()->institution_id);

        return redirect()->route('crm.compliance.erasure.index')
            ->with('success', 'PII erasure scheduled for 30 days from today.');
    }
}
