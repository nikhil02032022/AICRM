<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StartDiallerSessionRequest;
use App\Http\Resources\CRM\DiallerSessionResource;
use App\Models\CRM\DiallerSession;
use App\Services\CRM\Communication\DiallerService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-TC-001 — Integration API for dialler session control
final class DiallerController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DiallerService $diallerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $sessions = DiallerSession::query()
            ->with('starter')
            ->latest('created_at')
            ->paginate((int) $request->integer('per_page', 20));

        return $this->success(
            data: DiallerSessionResource::collection($sessions->items()),
            message: 'Dialler sessions fetched successfully.',
            meta: [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        );
    }

    public function store(StartDiallerSessionRequest $request): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $validated = $request->validated();

        $session = $this->diallerService->startSession(
            user: $request->user(),
            leadUuids: $validated['lead_uuids'] ?? [],
            campaignName: $validated['campaign_name'] ?? null,
            leadLimit: (int) ($validated['lead_limit'] ?? 25),
        );

        return $this->created(
            data: new DiallerSessionResource($session->load('starter')),
            message: 'Dialler session started successfully.',
        );
    }

    public function show(DiallerSession $diallerSession): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        return $this->success(
            data: new DiallerSessionResource($diallerSession->load(['starter', 'logs.lead', 'logs.callLog'])),
            message: 'Dialler session fetched successfully.',
        );
    }

    public function stop(DiallerSession $diallerSession): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $updated = $this->diallerService->stopSession($diallerSession);

        return $this->success(
            data: new DiallerSessionResource($updated->load('starter')),
            message: 'Dialler session stopped successfully.',
        );
    }

    public function dispatchNext(DiallerSession $diallerSession): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $this->diallerService->queueNext($diallerSession);

        return $this->success(
            data: new DiallerSessionResource($diallerSession->load('starter')),
            message: 'Dialler next call queued successfully.',
            status: 202,
        );
    }
}
