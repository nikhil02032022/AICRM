<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\TriggerAlumniBridgeRequest;
use App\Http\Resources\CRM\AlumniBridgeLogResource;
use App\Models\CRM\Lead;
use App\Services\CRM\Integration\AlumniBridgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// BRD: CRM-EI-008 — Alumni bridge API controller (Sanctum, external consumers only)
final class AlumniBridgeController extends Controller
{
    public function __construct(
        private readonly AlumniBridgeService $service
    ) {}

    /**
     * BRD: CRM-EI-008 — List alumni bridge logs (paginated)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = $this->service->list(
            $request->user()->institution_id,
            (int) $request->get('per_page', 20),
        );

        return AlumniBridgeLogResource::collection($logs);
    }

    /**
     * BRD: CRM-EI-008 — Trigger alumni bridge (ERP triggers this on student graduation)
     */
    public function store(TriggerAlumniBridgeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user      = $request->user();

        $lead = Lead::where('uuid', $validated['lead_uuid'])
            ->where('institution_id', $user->institution_id)
            ->firstOrFail();

        $log = $this->service->trigger(
            leadId:         $lead->id,
            institutionId:  $user->institution_id,
            campusId:       (int) ($user->campus_id ?? 0),
            erpStudentId:   $validated['erp_student_id'],
            payloadSummary: [],
        );

        return response()->json([
            'success' => true,
            'data'    => new AlumniBridgeLogResource($log),
            'message' => 'Alumni bridge triggered.',
        ], 201);
    }

    /**
     * BRD: CRM-EI-008 — Show specific alumni bridge log
     */
    public function show(string $uuid): JsonResponse
    {
        $log = $this->service->findByUuid($uuid);

        if ($log === null) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AlumniBridgeLogResource($log),
        ]);
    }
}
