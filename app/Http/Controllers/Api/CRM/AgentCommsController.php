<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Enums\CRM\AgentCommsChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreAgentCommsRequest;
use App\Http\Resources\CRM\AgentCommsLogResource;
use App\Services\CRM\Agent\AgentCommsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// BRD: CRM-AG-008 — Agent bulk comms API controller (Sanctum, external consumers only)
final class AgentCommsController extends Controller
{
    public function __construct(
        private readonly AgentCommsService $service
    ) {}

    /**
     * BRD: CRM-AG-008 — List bulk comms history (paginated)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = $this->service->list(
            $request->user()->institution_id,
            (int) $request->get('per_page', 20),
        );

        return AgentCommsLogResource::collection($logs);
    }

    /**
     * BRD: CRM-AG-008 — Dispatch bulk communication to agent network
     */
    public function store(StoreAgentCommsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user      = $request->user();

        $log = $this->service->send(
            institutionId:     $user->institution_id,
            campusId:          (int) ($user->campus_id ?? 0),
            sentByUserId:      $user->id,
            channel:           AgentCommsChannel::from($validated['channel']),
            messageBody:       $validated['message_body'],
            recipientAgentIds: $validated['recipient_agent_ids'],
            subject:           $validated['subject'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data'    => new AgentCommsLogResource($log),
            'message' => 'Bulk communication queued.',
        ], 201);
    }

    /**
     * BRD: CRM-AG-008 — Show specific comms log
     */
    public function show(string $uuid): JsonResponse
    {
        $log = $this->service->findByUuid($uuid);

        if ($log === null) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AgentCommsLogResource($log),
        ]);
    }
}
