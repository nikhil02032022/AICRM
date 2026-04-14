<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreAgentCommissionRequest;
use App\Http\Requests\CRM\UpdateAgentCommissionRequest;
use App\Http\Resources\CRM\AgentCommissionResource;
use App\Models\CRM\AgentCommission;
use App\Models\CRM\Lead;
use App\Services\CRM\Agent\AgentCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// BRD: CRM-AG-006 — Agent commission API controller (Sanctum, external consumers only)
final class AgentCommissionController extends Controller
{
    public function __construct(
        private readonly AgentCommissionService $service
    ) {}

    /**
     * BRD: CRM-AG-006 — List commission records (paginated)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $commissions = $this->service->list(
            $request->user()->institution_id,
            (int) $request->get('per_page', 20),
        );

        return AgentCommissionResource::collection($commissions);
    }

    /**
     * BRD: CRM-AG-006 — Create a commission record (ERP triggers on enrolment confirmation)
     */
    public function store(StoreAgentCommissionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user      = $request->user();

        $lead = Lead::where('uuid', $validated['lead_uuid'])
            ->where('institution_id', $user->institution_id)
            ->firstOrFail();

        $commission = $this->service->create(
            agentUserId:      (int) $validated['agent_user_id'],
            lead:             $lead,
            commissionType:   $validated['commission_type'],
            commissionAmount: (float) ($validated['commission_amount'] ?? 0),
            percentageRate:   isset($validated['percentage_rate']) ? (float) $validated['percentage_rate'] : null,
            baseAmount:       isset($validated['base_amount']) ? (float) $validated['base_amount'] : null,
        );

        return response()->json([
            'success' => true,
            'data'    => new AgentCommissionResource($commission),
            'message' => 'Commission record created.',
        ], 201);
    }

    /**
     * BRD: CRM-AG-006 — Show specific commission record
     */
    public function show(string $uuid): JsonResponse
    {
        $commission = $this->service->findByUuid($uuid);

        if ($commission === null) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AgentCommissionResource($commission),
        ]);
    }

    /**
     * BRD: CRM-AG-006 — Approve / reject / mark paid
     */
    public function update(UpdateAgentCommissionRequest $request, AgentCommission $agentCommission): JsonResponse
    {
        $validated = $request->validated();
        $user      = $request->user();

        $updated = match ($validated['action']) {
            'approve' => $this->service->approve($agentCommission, $user->id, $validated['approval_notes'] ?? null),
            'reject'  => $this->service->reject($agentCommission, $user->id, $validated['approval_notes'] ?? ''),
            'pay'     => $this->service->markPaid($agentCommission, $validated['payout_reference'] ?? ''),
            default   => $agentCommission,
        };

        return response()->json([
            'success' => true,
            'data'    => new AgentCommissionResource($updated),
            'message' => 'Commission updated.',
        ]);
    }
}
