<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\CRM\Agents;

use App\Http\Controllers\Controller;
use App\Models\CRM\Agents\Agent;
use App\Services\CRM\Agents\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// BRD: CRM-AG-001 — Agent CRUD via API (Sanctum auth)
final class AgentApiController extends Controller
{
    public function __construct(private readonly AgentService $agentService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agent::class);

        $agents = $this->agentService->list(
            $request->user()->institution_id,
            $request->only(['status', 'search']),
        );

        return response()->json(['success' => true, 'data' => $agents]);
    }

    public function show(Agent $agent): JsonResponse
    {
        $this->authorize('view', $agent);

        return response()->json(['success' => true, 'data' => $agent->load('referralCode', 'commissionStructures')]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Agent::class);

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255'],
            'mobile'          => ['nullable', 'string', 'max:20'],
            'password'        => ['required', 'string', 'min:8'],
            'agreement_start' => ['required', 'date'],
            'agreement_end'   => ['nullable', 'date'],
            'status'          => ['required', 'in:active,inactive,suspended'],
        ]);

        $validated['institution_id'] = $request->user()->institution_id;

        $agent = $this->agentService->create($validated);

        return response()->json(['success' => true, 'data' => $agent->load('referralCode')], 201);
    }

    public function update(Request $request, Agent $agent): JsonResponse
    {
        $this->authorize('update', $agent);

        $validated = $request->validate([
            'name'            => ['sometimes', 'string', 'max:255'],
            'mobile'          => ['nullable', 'string', 'max:20'],
            'agreement_end'   => ['nullable', 'date'],
            'status'          => ['sometimes', 'in:active,inactive,suspended'],
            'notes'           => ['nullable', 'string'],
        ]);

        $agent = $this->agentService->update($agent, $validated);

        return response()->json(['success' => true, 'data' => $agent]);
    }
}
