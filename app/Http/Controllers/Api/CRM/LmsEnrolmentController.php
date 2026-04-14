<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\TriggerLmsEnrolmentRequest;
use App\Http\Resources\CRM\LmsEnrolmentLogResource;
use App\Models\CRM\Lead;
use App\Services\CRM\Integration\LmsEnrolmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// BRD: CRM-EI-010 — LMS enrolment API controller (Sanctum, external consumers only)
final class LmsEnrolmentController extends Controller
{
    public function __construct(
        private readonly LmsEnrolmentService $service
    ) {}

    /**
     * BRD: CRM-EI-010 — List LMS enrolment logs (paginated)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = $this->service->list(
            $request->user()->institution_id,
            (int) $request->get('per_page', 20),
        );

        return LmsEnrolmentLogResource::collection($logs);
    }

    /**
     * BRD: CRM-EI-010 — Trigger LMS enrolment for an admitted student
     */
    public function store(TriggerLmsEnrolmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user      = $request->user();

        $lead = Lead::where('uuid', $validated['lead_uuid'])
            ->where('institution_id', $user->institution_id)
            ->firstOrFail();

        $log = $this->service->trigger(
            leadId:        $lead->id,
            institutionId: $user->institution_id,
            campusId:      (int) ($user->campus_id ?? 0),
            erpStudentId:  $validated['erp_student_id'],
            lmsProvider:   $validated['lms_provider'],
            lmsCourseId:   $validated['lms_course_id'],
        );

        return response()->json([
            'success' => true,
            'data'    => new LmsEnrolmentLogResource($log),
            'message' => 'LMS enrolment triggered.',
        ], 201);
    }

    /**
     * BRD: CRM-EI-010 — Show specific LMS enrolment log
     */
    public function show(string $uuid): JsonResponse
    {
        $log = $this->service->findByUuid($uuid);

        if ($log === null) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new LmsEnrolmentLogResource($log),
        ]);
    }
}
