<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StartDiallerSessionRequest;
use App\Models\CRM\DiallerSession;
use App\Models\CRM\Lead;
use App\Services\CRM\Communication\DiallerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// BRD: CRM-TC-001 — Web UI controller for power/auto-dialler session management
final class DiallerWebController extends Controller
{
    public function __construct(
        private readonly DiallerService $diallerService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.communication.send');

        $sessions = DiallerSession::query()
            ->with(['starter'])
            ->latest('created_at')
            ->paginate(12);

        $candidateLeads = Lead::query()
            ->where('assigned_counsellor_id', $request->user()->id)
            ->whereNull('dnc_at')
            ->where('opt_out', false)
            ->where('call_consent_given', true)
            ->whereNotNull('mobile')
            ->orderByDesc('lead_score')
            ->limit(50)
            ->get(['id', 'uuid', 'first_name', 'last_name', 'lead_score', 'status', 'temperature']);

        return view('crm.communication.voice.dialler', compact('sessions', 'candidateLeads'));
    }

    public function store(StartDiallerSessionRequest $request): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $validated = $request->validated();

        $session = $this->diallerService->startSession(
            user: Auth::user(),
            leadUuids: $validated['lead_uuids'] ?? [],
            campaignName: $validated['campaign_name'] ?? null,
            leadLimit: (int) ($validated['lead_limit'] ?? 25),
        );

        return redirect()
            ->route('crm.communication.voice.dialler.index')
            ->with('success', "Dialler started. Session ID: {$session->uuid}");
    }

    public function stop(DiallerSession $diallerSession): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $this->diallerService->stopSession($diallerSession);

        return back()->with('success', 'Dialler session stopped.');
    }

    public function dispatchNext(DiallerSession $diallerSession): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $this->diallerService->queueNext($diallerSession);

        return back()->with('success', 'Dialler next call queued.');
    }
}
