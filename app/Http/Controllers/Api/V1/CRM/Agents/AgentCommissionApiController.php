<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\CRM\Agents;

use App\Http\Controllers\Controller;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentCommissionAccrual;
use Illuminate\Http\JsonResponse;

// BRD: CRM-AG-005, AG-007 — Agent commission accrual read API
final class AgentCommissionApiController extends Controller
{
    public function index(Agent $agent): JsonResponse
    {
        $this->authorize('view', $agent);

        $accruals = $agent->commissionAccruals()
            ->with(['application', 'programme', 'structure'])
            ->latest('accrued_at')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $accruals]);
    }

    public function show(Agent $agent, AgentCommissionAccrual $accrual): JsonResponse
    {
        $this->authorize('view', $agent);

        abort_if($accrual->agent_id !== $agent->id, 404);

        return response()->json([
            'success' => true,
            'data'    => $accrual->load(['application', 'lead', 'programme', 'structure']),
        ]);
    }
}
