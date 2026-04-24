<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Compliance;

use App\Http\Controllers\Controller;
use App\Models\CRM\ConsentRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-CR-001, CR-002 — Consent records viewer (admin read-only)
final class ConsentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('crm.compliance.consent.view');

        $records = ConsentRecord::withoutGlobalScopes()
            ->where('institution_id', $request->user()->institution_id)
            ->when($request->input('lead_id'), fn ($q, $v) => $q->where('lead_id', $v))
            ->when($request->input('type'), fn ($q, $v) => $q->where('consent_type', $v))
            ->when($request->input('from'), fn ($q, $v) => $q->whereDate('consented_at', '>=', $v))
            ->when($request->input('to'), fn ($q, $v) => $q->whereDate('consented_at', '<=', $v))
            ->with('lead:id,first_name,last_name')
            ->orderByDesc('consented_at')
            ->paginate(50)
            ->withQueryString();

        return view('crm.compliance.consent.index', compact('records'));
    }

    public function show(int $id, Request $request): View
    {
        $this->authorize('crm.compliance.consent.view');

        $record = ConsentRecord::withoutGlobalScopes()
            ->where('institution_id', $request->user()->institution_id)
            ->with(['lead:id,first_name,last_name,mobile'])
            ->findOrFail($id);

        return view('crm.compliance.consent.show', compact('record'));
    }
}
