<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreCallDispositionConfigRequest;
use App\Http\Resources\CRM\CallDispositionConfigResource;
use App\Models\CRM\CallDispositionConfig;
use App\Services\CRM\Communication\CallDispositionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-TC-003 — API for configurable call dispositions
final class CallDispositionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CallDispositionService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('crm.communication.send');

        $institutionId = (int) $request->user()->institution_id;
        $this->service->ensureDefaults($institutionId, (int) $request->user()->id);

        $configs = $this->service->paginate($institutionId, (int) $request->input('per_page', 20));

        return $this->success(
            data: CallDispositionConfigResource::collection($configs->items()),
            message: 'Call dispositions fetched successfully.',
            meta: [
                'current_page' => $configs->currentPage(),
                'last_page' => $configs->lastPage(),
                'per_page' => $configs->perPage(),
                'total' => $configs->total(),
            ],
        );
    }

    public function store(StoreCallDispositionConfigRequest $request): JsonResponse
    {
        Gate::authorize('crm.settings.manage');

        $created = $this->service->create(
            institutionId: (int) $request->user()->institution_id,
            userId: (int) $request->user()->id,
            payload: $request->validated(),
        );

        return $this->created(
            data: new CallDispositionConfigResource($created),
            message: 'Call disposition created successfully.',
        );
    }

    public function update(StoreCallDispositionConfigRequest $request, CallDispositionConfig $callDispositionConfig): JsonResponse
    {
        Gate::authorize('crm.settings.manage');

        $updated = $this->service->update($callDispositionConfig, $request->validated());

        return $this->success(
            data: new CallDispositionConfigResource($updated),
            message: 'Call disposition updated successfully.',
        );
    }
}
