<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreReportScheduleRequest;
use App\Http\Resources\CRM\ReportScheduleResource;
use App\Models\CRM\ReportSchedule;
use App\Services\CRM\Analytics\ReportSchedulerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-AR-020 — API controller for scheduled report delivery (external integrations)
final class ReportSchedulerController extends Controller
{
    public function __construct(
        private readonly ReportSchedulerService $service,
    ) {}

    /** GET /api/v1/crm/reports/schedules */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('crm.reports.manage');

        $schedules = $this->service->paginate(
            $request->user()->institution_id,
            $request->only(['search']),
            (int) $request->input('per_page', 25),
        );

        return ReportScheduleResource::collection($schedules);
    }

    /** POST /api/v1/crm/reports/schedules */
    public function store(StoreReportScheduleRequest $request): JsonResponse
    {
        Gate::authorize('crm.reports.manage');

        $schedule = $this->service->create(
            $request->validated(),
            $request->user()->institution_id,
            $request->user()->id,
        );

        return (new ReportScheduleResource($schedule->load('customReport')))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/v1/crm/reports/schedules/{reportSchedule:uuid} */
    public function show(ReportSchedule $reportSchedule): ReportScheduleResource
    {
        Gate::authorize('crm.reports.manage');

        return new ReportScheduleResource($reportSchedule->load('customReport'));
    }

    /** PUT /api/v1/crm/reports/schedules/{reportSchedule:uuid} */
    public function update(StoreReportScheduleRequest $request, ReportSchedule $reportSchedule): ReportScheduleResource
    {
        Gate::authorize('crm.reports.manage');

        $updated = $this->service->update($reportSchedule, $request->validated());

        return new ReportScheduleResource($updated->load('customReport'));
    }

    /** DELETE /api/v1/crm/reports/schedules/{reportSchedule:uuid} */
    public function destroy(ReportSchedule $reportSchedule): JsonResponse
    {
        Gate::authorize('crm.reports.manage');

        $this->service->delete($reportSchedule);

        return response()->json(['success' => true, 'message' => 'Schedule deleted.']);
    }

    /** POST /api/v1/crm/reports/schedules/{reportSchedule:uuid}/dispatch */
    public function dispatch(ReportSchedule $reportSchedule): JsonResponse
    {
        Gate::authorize('crm.reports.manage');

        $delivery = $this->service->dispatchDelivery($reportSchedule);

        return response()->json([
            'success' => true,
            'data'    => ['delivery_uuid' => $delivery->uuid],
            'message' => 'Delivery queued.',
        ]);
    }
}
