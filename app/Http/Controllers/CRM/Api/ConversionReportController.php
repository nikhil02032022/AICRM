<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\ConversionReportRequest;
use App\Exports\CRM\ConversionRateExport;
use App\Http\Resources\CRM\ConversionRateResource;
use App\Http\Resources\CRM\ConversionReportResource;
use App\Services\CRM\Application\ConversionReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConversionReportController extends Controller
{
    public function __construct(
        protected ConversionReportService $reportService
    ) {}

    /**
     * Get conversion report grouped by programme, source, and counsellor.
     *
     * @param ConversionReportRequest $request
     * @return JsonResponse
     */
    /**
     * @param ConversionReportRequest $request
     * @return JsonResponse|BinaryFileResponse
     */
    public function index(ConversionReportRequest $request)
    {
        Gate::authorize('crm.analytics.view');

        $filters = $request->validated();
        $stats = $this->reportService->getGroupedStats($filters);

        // Export support: Accept header or ?export=csv|xlsx
        $exportType = $request->get('export') ?? $request->header('Accept');
        if (str_contains($exportType, 'text/csv') || $exportType === 'csv') {
            $response = Excel::download(new \App\Exports\CRM\ConversionReportExport($stats), 'conversion_report.csv', \Maatwebsite\Excel\Excel::CSV);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            return $response;
        }
        if (str_contains($exportType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') || $exportType === 'xlsx') {
            return Excel::download(new \App\Exports\CRM\ConversionReportExport($stats), 'conversion_report.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        return response()->json([
            'success' => true,
            'data' => ConversionReportResource::collection($stats),
            'message' => 'Conversion report fetched successfully.',
            'meta' => [
                'count' => $stats->count(),
            ],
        ]);
    }

    /**
     * BRD: CRM-AP-019 — Conversion rate report by programme, batch, source, and counsellor.
     *
     * @param ConversionReportRequest $request
     * @return JsonResponse|BinaryFileResponse
     */
    public function rates(ConversionReportRequest $request)
    {
        Gate::authorize('crm.analytics.view');

        $filters = $request->validated();
        $stats = $this->reportService->getConversionRates($filters);

        $exportType = $request->get('export') ?? $request->header('Accept');
        if (str_contains((string) $exportType, 'text/csv') || $exportType === 'csv') {
            $response = Excel::download(new ConversionRateExport($stats), 'conversion_rates.csv', \Maatwebsite\Excel\Excel::CSV);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            return $response;
        }
        if (str_contains((string) $exportType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') || $exportType === 'xlsx') {
            return Excel::download(new ConversionRateExport($stats), 'conversion_rates.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        return response()->json([
            'success' => true,
            'data'    => ConversionRateResource::collection($stats),
            'message' => 'Conversion rate report fetched successfully.',
            'meta'    => ['count' => $stats->count()],
        ]);
    }
}
