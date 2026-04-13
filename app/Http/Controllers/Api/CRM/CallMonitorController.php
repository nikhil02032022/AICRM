<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StopCallMonitorRequest;
use App\Http\Requests\CRM\StoreCallMonitorRequest;
use App\Http\Resources\CRM\CallMonitorLogResource;
use App\Models\CRM\CallLog;
use App\Models\CRM\CallMonitorLog;
use App\Services\CRM\Communication\CallMonitorService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

// BRD: CRM-TC-005 — Integration API for supervisor call monitoring
final class CallMonitorController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CallMonitorService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $monitorSessions = $this->service->paginateSessions($request->only(['status', 'mode']), (int) $request->input('per_page', 20));
        $activeCalls = $this->service->activeCalls(20);

        return $this->success(
            data: [
                'sessions' => CallMonitorLogResource::collection($monitorSessions->items()),
                'active_calls' => collect($activeCalls->items())->map(static function (CallLog $callLog): array {
                    return [
                        'uuid' => $callLog->uuid,
                        'status' => $callLog->status->value,
                        'called_at' => $callLog->called_at?->toIso8601String(),
                        'lead_name' => $callLog->lead?->name,
                    ];
                })->all(),
            ],
            message: 'Call monitoring data fetched successfully.',
            meta: [
                'current_page' => $monitorSessions->currentPage(),
                'last_page' => $monitorSessions->lastPage(),
                'per_page' => $monitorSessions->perPage(),
                'total' => $monitorSessions->total(),
            ],
        );
    }

    public function store(StoreCallMonitorRequest $request): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $callLog = CallLog::query()->where('uuid', (string) $request->validated('call_log_uuid'))->firstOrFail();

        try {
            $session = $this->service->startSession($callLog, (int) $request->user()->id, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 'MONITOR_START_BLOCKED', 422);
        }

        return $this->created(
            data: new CallMonitorLogResource($session->load(['callLog.lead', 'supervisor'])),
            message: 'Monitoring session started successfully.',
        );
    }

    public function stop(StopCallMonitorRequest $request, CallMonitorLog $callMonitorLog): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        try {
            $stopped = $this->service->stopSession(
                monitorLog: $callMonitorLog,
                supervisorId: (int) $request->user()->id,
                notes: $request->validated('notes'),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 'MONITOR_STOP_BLOCKED', 422);
        }

        return $this->success(
            data: new CallMonitorLogResource($stopped),
            message: 'Monitoring session ended successfully.',
        );
    }
}
