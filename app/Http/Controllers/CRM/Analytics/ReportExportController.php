<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Analytics;

use App\Http\Controllers\Controller;
use App\Services\CRM\Analytics\DashboardScopeService;
use App\Services\CRM\Analytics\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// BRD: CRM-AR-019 — Export any standard report to Excel or PDF via a single parametric endpoint
final class ReportExportController extends Controller
{
    private const VALID_REPORTS = [
        'enquiry-register',
        'counsellor-activity',
        'application-status',
        'source-effectiveness',
        'lost-lead-analysis',
        'fee-collection',
        'document-compliance',
        'year-on-year',
        'agent-performance',
    ];

    public function __construct(
        private readonly DashboardScopeService $scopeService,
        private readonly ReportExportService   $exportService,
    ) {}

    /**
     * GET /crm/analytics/reports/{report}/export?format=excel|pdf&from=...&to=...
     *
     * All filter parameters are forwarded as-is; each report's service method
     * picks only the keys it understands.
     */
    public function export(Request $request, string $report): BinaryFileResponse|Response
    {
        Gate::authorize('crm.reports.export');

        abort_unless(in_array($report, self::VALID_REPORTS, strict: true), 404);

        $format = $request->input('format', 'excel');

        abort_unless(in_array($format, ['excel', 'pdf'], strict: true), 422, 'Unsupported export format.');

        $scope = $this->scopeService->resolveScope($request->user());

        // Forward all query string parameters as filters; each service method
        // only reads the keys it needs, so extra keys are harmlessly ignored.
        $filters = $request->except(['format', '_token']);

        return $format === 'pdf'
            ? $this->exportService->downloadPdf($report, $scope, $filters)
            : $this->exportService->downloadExcel($report, $scope, $filters);
    }
}
