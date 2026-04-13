<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\ResolveCallScriptBranchRequest;
use App\Http\Requests\CRM\StoreCallScriptRequest;
use App\Http\Resources\CRM\CallScriptResource;
use App\Http\Resources\CRM\CallScriptStepResource;
use App\Models\CRM\CallScript;
use App\Services\CRM\Communication\CallScriptService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-TC-002 — Integration API for call scripts and branch resolution
final class CallScriptController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CallScriptService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $scripts = $this->service->paginate($request->only(['status', 'search']), (int) $request->input('per_page', 20));

        return $this->success(
            data: CallScriptResource::collection($scripts->items()),
            message: 'Call scripts fetched successfully.',
            meta: [
                'current_page' => $scripts->currentPage(),
                'last_page' => $scripts->lastPage(),
                'per_page' => $scripts->perPage(),
                'total' => $scripts->total(),
            ],
        );
    }

    public function store(StoreCallScriptRequest $request): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $script = $this->service->create(
            payload: $request->validated(),
            institutionId: (int) $request->user()->institution_id,
            createdBy: (int) $request->user()->id,
        );

        return $this->created(
            data: new CallScriptResource($script->load('steps')),
            message: 'Call script created successfully.',
        );
    }

    public function show(CallScript $callScript): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        return $this->success(
            data: new CallScriptResource($callScript->load('steps')),
            message: 'Call script fetched successfully.',
        );
    }

    public function update(StoreCallScriptRequest $request, CallScript $callScript): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $updated = $this->service->update($callScript, $request->validated());

        return $this->success(
            data: new CallScriptResource($updated->load('steps')),
            message: 'Call script updated successfully.',
        );
    }

    public function destroy(CallScript $callScript): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $this->service->delete($callScript);

        return $this->success(null, 'Call script archived successfully.');
    }

    public function resolve(ResolveCallScriptBranchRequest $request, CallScript $callScript): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $current = $this->service->stepByKey($callScript, (string) $request->validated('current_step_key'));
        $next = $this->service->resolveNextStep(
            script: $callScript,
            currentStepKey: (string) $request->validated('current_step_key'),
            response: $request->validated('response'),
        );

        return $this->success(
            data: [
                'current_step' => $current ? new CallScriptStepResource($current) : null,
                'next_step' => $next ? new CallScriptStepResource($next) : null,
                'is_terminal' => $next === null,
            ],
            message: 'Call script branch resolved successfully.',
        );
    }
}
