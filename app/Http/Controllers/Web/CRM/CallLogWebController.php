<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\CallLog;
use App\Models\CRM\Lead;
use App\Services\CRM\Communication\CallDispositionService;
use App\Services\CRM\Communication\VoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// BRD: CRM-CC-017, CRM-CC-018 — Call log management and click-to-call (web)
final class CallLogWebController extends Controller
{
    public function __construct(
        private readonly VoiceService $voiceService,
        private readonly CallDispositionService $callDispositionService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.communication.send');

        $institutionId = (int) $request->user()->institution_id;
        $this->callDispositionService->ensureDefaults($institutionId, (int) $request->user()->id);

        $activeDispositions = $this->callDispositionService->activeForInstitution($institutionId);
        $dispositionOptions = $activeDispositions
            ->mapWithKeys(static fn ($config): array => [$config->code => $config->label])
            ->all();
        $dispositionLabelMap = $activeDispositions
            ->mapWithKeys(static fn ($config): array => [$config->code => $config->label])
            ->all();

        $search = trim((string) $request->query('search', ''));
        $hasRecording = (string) $request->query('has_recording', 'all');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $callLogs = CallLog::with(['lead', 'initiatedBy'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('provider_call_id', 'like', "%{$search}%")
                        ->orWhereHas('lead', function ($leadQuery) use ($search): void {
                            $leadQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('initiatedBy', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($hasRecording === 'yes', fn ($query) => $query->whereNotNull('recording_url'))
            ->when($hasRecording === 'no', fn ($query) => $query->whereNull('recording_url'))
            ->when(is_string($fromDate) && $fromDate !== '', fn ($query) => $query->whereDate('called_at', '>=', $fromDate))
            ->when(is_string($toDate) && $toDate !== '', fn ($query) => $query->whereDate('called_at', '<=', $toDate))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('crm.communication.voice.index', compact('callLogs', 'dispositionOptions', 'dispositionLabelMap', 'search', 'hasRecording', 'fromDate', 'toDate'));
    }

    // BRD: CRM-TC-008 — Consent-gated recording playback
    public function playRecording(CallLog $callLog): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        if (! $callLog->call_consent_given || empty($callLog->recording_url)) {
            return back()->with('error', 'Recording not available for this call.');
        }

        return redirect()->away($callLog->recording_url);
    }

    public function initiateCall(Lead $lead): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $callLog = $this->voiceService->initiateClickToCall($lead, Auth::user());

        return redirect()
            ->route('crm.leads.show', $lead->uuid)
            ->with('success', "Call initiated. Call ID: {$callLog->uuid}");
    }

    public function updateDisposition(Request $request, CallLog $callLog): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $institutionId = (int) $request->user()->institution_id;
        $this->callDispositionService->ensureDefaults($institutionId, (int) $request->user()->id);

        $outcome = $request->validate([
            'disposition'       => [
                'required',
                'string',
                Rule::exists('call_disposition_configs', 'code')->where(function ($query) use ($institutionId): void {
                    $query->where('institution_id', $institutionId)->where('is_active', true);
                }),
            ],
            'disposition_notes' => ['nullable', 'string', 'max:1000'],
            'duration_seconds'  => ['nullable', 'integer', 'min:0'],
        ]);

        $this->voiceService->finaliseCallLog($callLog, $outcome);

        $mustPromptFollowUp =
            $callLog->lead !== null
            && $this->callDispositionService->shouldPromptFollowUp($institutionId, (string) $outcome['disposition'])
            && $request->user()->can('crm.sessions.create');

        if ($mustPromptFollowUp) {
            $prompt = 'Call disposition saved. Please schedule the next follow-up now.';

            return redirect()
                ->route('crm.leads.sessions.create', $callLog->lead->uuid)
                ->with('success', $prompt)
                ->with('follow_up_prompt', [
                    'call_log_uuid' => $callLog->uuid,
                    'disposition' => $outcome['disposition'],
                    'disposition_label' => $this->callDispositionService->labelForCode($institutionId, (string) $outcome['disposition']) ?? $outcome['disposition'],
                ]);
        }

        return back()->with('success', 'Call disposition recorded.');
    }
}
