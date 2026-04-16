<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Enums\CRM\ApplicationStatus;
use App\Http\Resources\CRM\ApplicationResource;
use App\Models\CRM\Application;
use App\Repositories\CRM\Application\ApplicationRepositoryInterface;
use App\Services\CRM\Application\ApplicationPipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-AP-008, CRM-AP-009 — RESTful API for application pipeline (mobile, third-party)
final class ApplicationPipelineController
{
    public function __construct(
        private readonly ApplicationRepositoryInterface $repository,
        private readonly ApplicationPipelineService $pipelineService,
    ) {}

    /**
     * List applications with filtering, pagination.
     * GET /api/v1/crm/applications
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('crm.applications.view');

        $filters = [
            'programme_id' => $request->query('programme_id'),
            'batch' => $request->query('batch'),
            'source' => $request->query('source'),
            'status' => $request->query('status'),
            'assigned_counsellor_id' => $request->query('counsellor_id'),
            'admission_cycle_uuid' => $request->query('admission_cycle_uuid'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'score_min' => $request->query('score_min'),
            'score_max' => $request->query('score_max'),
            'search' => $request->query('q'),
        ];

        $perPage = (int) $request->query('per_page', 20);
        $items = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => ApplicationResource::collection($items),
            'meta' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single application.
     * GET /api/v1/crm/applications/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        $application = $this->repository->findByUuidOrFail($uuid);
        Gate::authorize('crm.applications.view', $application);

        return response()->json([
            'success' => true,
            'data' => new ApplicationResource($application),
        ]);
    }

    /**
     * Transition application to new status.
     * POST /api/v1/crm/applications/{uuid}/transition
     * BRD: CRM-AP-009
     */
    public function transition(string $uuid, Request $request): JsonResponse
    {
        $application = $this->repository->findByUuidOrFail($uuid);
        Gate::authorize('transition', $application);

        $validated = $request->validate([
            'status' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $application = $this->pipelineService->transition(
                $application,
                ApplicationStatus::from($validated['status']),
                Auth::id(),
                $validated['reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Application status updated successfully',
                'data' => new ApplicationResource($application),
            ]);

        } catch (\ValueError $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATUS',
                    'message' => 'Invalid application status',
                ],
            ], 422);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_TRANSITION',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    /**
     * Get seat availability for a programme.
     * GET /api/v1/crm/programmes/{programme_uuid}/seat-availability
     * BRD: CRM-AP-011
     */
    public function seatAvailability(string $programmeUuid): JsonResponse
    {
        Gate::authorize('crm.applications.view');

        $availability = $this->pipelineService->checkSeatAvailability($programmeUuid);

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    /**
     * Get conversion funnel metrics for analytics.
     * GET /api/v1/crm/applications/analytics/funnel
     * BRD: CRM-AP-018, CRM-AP-019
     */
    public function conversionFunnel(Request $request): JsonResponse
    {
        Gate::authorize('crm.applications.view');

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $institutionId = $currentUser->institution_id;
        $filters = [
            'admission_cycle_uuid' => $request->query('admission_cycle_uuid'),
        ];

        $counts = $this->pipelineService->countByStatus($institutionId, $filters);

        // Calculate conversion percentages
        $total = array_sum($counts);
        $funnel = [];
        foreach ($counts as $status => $count) {
            $funnel[$status] = [
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $funnel,
        ]);
    }
}
