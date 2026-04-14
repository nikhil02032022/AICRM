<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreCustomReportRequest;
use App\Http\Resources\CRM\CustomReportResource;
use App\Models\CRM\CustomReport;
use App\Services\CRM\Analytics\CustomReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-AR-018 — API controller for custom report builder (external integrations)
final class CustomReportController extends Controller
{
    public function __construct(
        private readonly CustomReportService $service,
    ) {}

    /** GET /api/v1/crm/reports/custom */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', CustomReport::class);

        $reports = $this->service->paginate(
            $request->only(['entity', 'search']),
            (int) $request->input('per_page', 25),
        );

        return CustomReportResource::collection($reports);
    }

    /** POST /api/v1/crm/reports/custom */
    public function store(StoreCustomReportRequest $request): JsonResponse
    {
        Gate::authorize('create', CustomReport::class);

        $report = $this->service->create(
            $request->validated(),
            $request->user()->institution_id,
            $request->user()->id,
        );

        return (new CustomReportResource($report->load('createdBy')))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/v1/crm/reports/custom/{customReport:uuid} */
    public function show(CustomReport $customReport): CustomReportResource
    {
        Gate::authorize('view', $customReport);

        return new CustomReportResource($customReport->load('createdBy'));
    }

    /** PUT /api/v1/crm/reports/custom/{customReport:uuid} */
    public function update(StoreCustomReportRequest $request, CustomReport $customReport): CustomReportResource
    {
        Gate::authorize('update', $customReport);

        $updated = $this->service->update($customReport, $request->validated());

        return new CustomReportResource($updated->load('createdBy'));
    }

    /** DELETE /api/v1/crm/reports/custom/{customReport:uuid} */
    public function destroy(CustomReport $customReport): JsonResponse
    {
        Gate::authorize('delete', $customReport);

        $this->service->delete($customReport);

        return response()->json(['success' => true, 'message' => 'Report deleted.']);
    }

    /** POST /api/v1/crm/reports/custom/{customReport:uuid}/run */
    public function run(CustomReport $customReport): JsonResponse
    {
        Gate::authorize('view', $customReport);

        $result = $this->service->run($customReport);

        return response()->json([
            'success' => true,
            'data'    => $result,
            'message' => "{$result['total']} rows returned.",
        ]);
    }
}
