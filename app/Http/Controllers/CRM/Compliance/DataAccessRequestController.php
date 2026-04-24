<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Compliance;

use App\Http\Controllers\Controller;
use App\Models\CRM\Compliance\DataAccessRequest;
use App\Services\CRM\Compliance\DataAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-CR-004 — Right-to-access: applicant can request a copy of stored data
final class DataAccessRequestController extends Controller
{
    public function __construct(private readonly DataAccessService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.compliance.data-access.view');

        $requests = DataAccessRequest::where('institution_id', $request->user()->institution_id)
            ->with('lead:id,first_name,last_name,uuid')
            ->orderByDesc('requested_at')
            ->get();

        return view('crm.compliance.data-access.index', compact('requests'));
    }

    public function show(DataAccessRequest $dataAccessRequest): View
    {
        $this->authorize('crm.compliance.data-access.view');

        $compiled = $this->service->compile($dataAccessRequest);

        return view('crm.compliance.data-access.show', compact('dataAccessRequest', 'compiled'));
    }

    public function process(DataAccessRequest $dataAccessRequest): RedirectResponse
    {
        $this->authorize('crm.compliance.data-access.process');

        $this->service->deliver($dataAccessRequest);

        return redirect()->route('crm.compliance.data-access.index')
            ->with('success', 'Data access request processed and data delivered.');
    }
}
