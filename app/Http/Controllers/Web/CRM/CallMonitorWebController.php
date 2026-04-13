<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StopCallMonitorRequest;
use App\Http\Requests\CRM\StoreCallMonitorRequest;
use App\Models\CRM\CallLog;
use App\Models\CRM\CallMonitorLog;
use App\Services\CRM\Communication\CallMonitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

// BRD: CRM-TC-005 — Web UI controller for supervisor call monitoring
final class CallMonitorWebController extends Controller
{
    public function __construct(
        private readonly CallMonitorService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.communication.send');

        $monitorSessions = $this->service->paginateSessions($request->only(['status', 'mode']), 20);
        $activeCalls = $this->service->activeCalls(20);

        return view('crm.communication.voice.call-monitor', [
            'monitorSessions' => $monitorSessions,
            'activeCalls' => $activeCalls,
        ]);
    }

    public function store(StoreCallMonitorRequest $request): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        $callLog = CallLog::query()->where('uuid', (string) $request->validated('call_log_uuid'))->firstOrFail();

        try {
            $this->service->startSession($callLog, (int) Auth::id(), $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['monitor' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'Monitoring session started successfully.');
    }

    public function stop(StopCallMonitorRequest $request, CallMonitorLog $callMonitorLog): RedirectResponse
    {
        $this->authorize('crm.communication.send');

        try {
            $this->service->stopSession(
                monitorLog: $callMonitorLog,
                supervisorId: (int) Auth::id(),
                notes: $request->validated('notes'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['monitor' => $exception->getMessage()]);
        }

        return back()->with('success', 'Monitoring session ended successfully.');
    }
}
