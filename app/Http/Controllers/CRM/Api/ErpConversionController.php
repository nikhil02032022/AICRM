<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Api;

use App\Http\Requests\CRM\ErpConversion\TriggerErpConversionRequest;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Repositories\CRM\Application\ApplicationConversionLogRepositoryInterface;
use App\Services\CRM\Erp\ErpConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-AP-016 — ERP conversion trigger, status, retry, and listing endpoints
final class ErpConversionController
{
    public function __construct(
        private readonly ErpConversionService $conversionService,
        private readonly ApplicationConversionLogRepositoryInterface $conversionLogRepository,
    ) {}

    /**
     * Trigger ERP conversion for an application.
     * POST /api/v1/crm/applications/{application:uuid}/convert
     * BRD: CRM-AP-016
     */
    public function trigger(TriggerErpConversionRequest $request, Application $application): JsonResponse
    {
        Gate::authorize('convert', $application);

        $log = $this->conversionService->convert($application, (int) $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'ERP conversion queued. The application will be enrolled once the ERP confirms registration.',
            'data'    => $this->logToArray($log),
        ], 202);
    }

    /**
     * Get conversion log for an application.
     * GET /api/v1/crm/applications/{application:uuid}/conversion
     */
    public function showForApplication(Application $application): JsonResponse
    {
        Gate::authorize('view', $application);

        $log = $this->conversionLogRepository->findByApplicationUuid($application->uuid);

        return response()->json([
            'success' => true,
            'data'    => $log ? $this->logToArray($log) : null,
        ]);
    }

    /**
     * List all conversion logs with optional filters.
     * GET /api/v1/crm/conversions
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ApplicationConversionLog::class);

        $filters = $request->only(['status', 'from_date', 'to_date']);
        $paginator = $this->conversionLogRepository->paginate($filters, 20);

        return response()->json([
            'success' => true,
            'data'    => collect($paginator->items())->map(fn ($log) => $this->logToArray($log)),
            'meta'    => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
            ],
        ]);
    }

    /**
     * Manually retry a failed conversion.
     * POST /api/v1/crm/conversions/{log:uuid}/retry
     */
    public function retry(ApplicationConversionLog $log): JsonResponse
    {
        Gate::authorize('retry', $log);

        $log = $this->conversionService->retry($log);

        return response()->json([
            'success' => true,
            'message' => 'ERP conversion retry queued.',
            'data'    => $this->logToArray($log),
        ], 202);
    }

    /** @return array<string, mixed> */
    private function logToArray(ApplicationConversionLog $log): array
    {
        return [
            'uuid'             => $log->uuid,
            'application_uuid' => $log->application_uuid,
            'lead_uuid'        => $log->lead_uuid,
            'erp_student_id'   => $log->erp_student_id,
            'status'           => $log->status,
            'retry_count'      => $log->retry_count,
            'attempted_at'     => $log->attempted_at?->toIso8601String(),
            'completed_at'     => $log->completed_at?->toIso8601String(),
            'next_retry_at'    => $log->next_retry_at?->toIso8601String(),
            'error_message'    => $log->error_message,
        ];
    }
}
