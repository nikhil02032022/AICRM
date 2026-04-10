<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\CallLog;
use App\Models\CRM\Lead;
use App\Services\CRM\Communication\VoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// BRD: CRM-CC-017, CRM-CC-018 — Call log management and click-to-call (web)
final class CallLogWebController extends Controller
{
    public function __construct(
        private readonly VoiceService $voiceService,
    ) {}

    public function index(): View
    {
        $this->authorize('crm.communication.send');

        $callLogs = CallLog::with(['lead', 'initiatedBy'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('crm.communication.voice.index', compact('callLogs'));
    }

    public function initiateCall(Lead $lead): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $callLog = $this->voiceService->initiateClickToCall($lead, Auth::user());

        return redirect()
            ->route('crm.leads.show', $lead->uuid)
            ->with('success', "Call initiated. Call ID: {$callLog->uuid}");
    }

    public function updateDisposition(CallLog $callLog): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $outcome = request()->validate([
            'disposition'       => ['required', 'string'],
            'disposition_notes' => ['nullable', 'string', 'max:1000'],
            'duration_seconds'  => ['nullable', 'integer', 'min:0'],
        ]);

        $this->voiceService->finaliseCallLog($callLog, $outcome);

        return back()->with('success', 'Call disposition recorded.');
    }
}
