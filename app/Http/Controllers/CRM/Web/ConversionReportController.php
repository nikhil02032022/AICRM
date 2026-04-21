<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\ConversionReportRequest;
use App\Services\CRM\Application\ConversionReportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ConversionReportController extends Controller
{
    public function __construct(
        protected ConversionReportService $reportService
    ) {}

    /**
     * @param ConversionReportRequest $request
     * @return View|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function index(ConversionReportRequest $request)
    {
        Gate::authorize('crm.analytics.view');
        $filters = $request->validated();
        $stats = $this->reportService->getGroupedStats($filters);
        $exportType = $request->get('export');
        if ($exportType === 'csv') {
            $response = \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CRM\ConversionReportExport($stats), 'conversion_report.csv', \Maatwebsite\Excel\Excel::CSV);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            return $response;
        }
        if ($exportType === 'xlsx') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CRM\ConversionReportExport($stats), 'conversion_report.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }
        return view('crm.analytics.conversion_report', [
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * BRD: CRM-AP-019 — Conversion rate report view.
     *
     * @param ConversionReportRequest $request
     * @return View|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function rates(ConversionReportRequest $request)
    {
        Gate::authorize('crm.analytics.view');
        $filters = $request->validated();
        $stats = $this->reportService->getConversionRates($filters);

        $exportType = $request->get('export');
        if ($exportType === 'csv') {
            $response = \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CRM\ConversionRateExport($stats), 'conversion_rates.csv', \Maatwebsite\Excel\Excel::CSV);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            return $response;
        }
        if ($exportType === 'xlsx') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CRM\ConversionRateExport($stats), 'conversion_rates.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        return view('crm.analytics.conversion_rates');
    }
}
