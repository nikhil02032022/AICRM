<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use App\Exports\CRM\Analytics\StandardReportExport;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// BRD: CRM-AR-019 — Orchestrate Excel and PDF exports for all 9 standard reports
final class ReportExportService
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Stream an Excel download for the given report type.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     */
    public function downloadExcel(string $reportType, array $scope, array $filters): BinaryFileResponse
    {
        ['title' => $title, 'rows' => $rows] = $this->getExportData($reportType, $scope, $filters);

        $filename = $this->filename($reportType, $filters) . '.xlsx';

        return Excel::download(
            new StandardReportExport($reportType, $rows, $title),
            $filename,
        );
    }

    /**
     * Stream a PDF download for the given report type.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     */
    public function downloadPdf(string $reportType, array $scope, array $filters): Response
    {
        ['title' => $title, 'headings' => $headings, 'rows' => $rows, 'filterDetails' => $filterDetails] =
            $this->getExportData($reportType, $scope, $filters);

        $html = view('crm.analytics.reports.pdf.report', [
            'title'         => $title,
            'headings'      => $headings,
            'rows'          => $rows,
            'filterSummary' => $this->filterSummary($filters),
            'filterDetails' => $filterDetails,
            'generatedAt'   => now()->format('d M Y H:i'),
        ])->render();

        $pdf = new Html2Pdf('L', 'A4', 'en', true, 'UTF-8', [8, 8, 8, 8]);
        $pdf->setDefaultFont('Arial');
        $pdf->writeHTML($html);

        $filename = $this->filename($reportType, $filters) . '.pdf';
        $content  = $pdf->output('', 'S');

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Data resolution per report type
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch and normalise report data into rows suitable for Excel/PDF.
     *
     * Returns array keys: title (string), headings (array), rows (Collection of flat arrays), filterDetails (array).
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @param array<string, mixed> $filters
     * @return array{title: string, headings: array<int,string>, rows: Collection<int,array<int,mixed>>, filterDetails: array<string,string|null>}
     */
    private function getExportData(string $reportType, array $scope, array $filters): array
    {
        return match ($reportType) {
            'enquiry-register'    => $this->enquiryRegisterData($scope, $filters),
            'counsellor-activity' => $this->counsellorActivityData($scope, $filters),
            'application-status'  => $this->applicationStatusData($scope, $filters),
            'source-effectiveness'=> $this->sourceEffectivenessData($scope, $filters),
            'lost-lead-analysis'  => $this->lostLeadAnalysisData($scope, $filters),
            'fee-collection'      => $this->feeCollectionData($scope, $filters),
            'document-compliance' => $this->documentComplianceData($scope, $filters),
            'year-on-year'        => $this->yearOnYearData($scope, $filters),
            'agent-performance'   => $this->agentPerformanceData($scope, $filters),
            default               => throw new \InvalidArgumentException("Unknown report type: {$reportType}"),
        };
    }

    // ─── per-report data builders ─────────────────────────────────────────────

    /** @return array{title: string, headings: array, rows: Collection, filterDetails: array} */
    private function enquiryRegisterData(array $scope, array $filters): array
    {
        $raw = $this->reportService->enquiryRegisterForExport($scope, $filters);

        $rows = $raw->map(fn ($lead) => [
            $lead->created_at?->format('d/m/Y'),
            trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')),
            $lead->mobile,
            $lead->email,
            $lead->source,
            $lead->status,
            $lead->campus?->name,
            $lead->programmeInterests->first()?->name,
            $lead->assignedCounsellor?->name,
        ]);

        return [
            'title'         => 'Enquiry Register',
            'headings'      => ['Date', 'Name', 'Mobile', 'Email', 'Source', 'Status', 'Campus', 'Programme', 'Counsellor'],
            'rows'          => $rows,
            'filterDetails' => $this->dateFilterDetails($filters),
        ];
    }

    /** @return array{title: string, headings: array, rows: Collection, filterDetails: array} */
    private function counsellorActivityData(array $scope, array $filters): array
    {
        $raw = $this->reportService->counsellorActivity($scope, $filters);

        $rows = $raw->map(fn ($c) => [
            $c->name,
            $c->campus?->name,
            (int) $c->new_leads,
            (int) $c->converted_leads,
            $c->new_leads > 0 ? round($c->converted_leads / $c->new_leads * 100, 1) . '%' : '0%',
            (int) $c->tasks_completed,
            (int) $c->tasks_overdue,
            (int) $c->calls_made,
            (int) $c->sessions_completed,
        ]);

        return [
            'title'         => 'Counsellor Activity Report',
            'headings'      => ['Counsellor', 'Campus', 'New Leads', 'Converted', 'Conversion %', 'Tasks Completed', 'Tasks Overdue', 'Calls Made', 'Sessions'],
            'rows'          => $rows,
            'filterDetails' => $this->dateFilterDetails($filters),
        ];
    }

    /** @return array{title: string, headings: array, rows: Collection, filterDetails: array} */
    private function applicationStatusData(array $scope, array $filters): array
    {
        $raw = $this->reportService->applicationStatusForExport($scope, $filters);

        $rows = $raw->map(fn ($app) => [
            $app->submitted_at?->format('d/m/Y'),
            trim(($app->lead?->first_name ?? '') . ' ' . ($app->lead?->last_name ?? '')),
            $app->lead?->mobile,
            $app->programme?->name,
            $app->campus?->name,
            $app->status?->value ?? $app->status,
            $app->assignedCounsellor?->name,
        ]);

        return [
            'title'         => 'Application Status Report',
            'headings'      => ['Submitted', 'Applicant', 'Mobile', 'Programme', 'Campus', 'Status', 'Counsellor'],
            'rows'          => $rows,
            'filterDetails' => $this->dateFilterDetails($filters),
        ];
    }

    /** @return array{title: string, headings: array, rows: Collection, filterDetails: array} */
    private function sourceEffectivenessData(array $scope, array $filters): array
    {
        $raw = $this->reportService->sourceEffectiveness($scope, $filters);

        $rows = $raw->map(fn ($row) => [
            $row->source,
            (int) $row->total_leads,
            (int) $row->applied,
            (int) $row->offered,
            (int) $row->enrolled,
            $row->total_leads > 0 ? round($row->applied  / $row->total_leads * 100, 1) . '%' : '0%',
            $row->applied      > 0 ? round($row->enrolled / $row->applied     * 100, 1) . '%' : '0%',
            $row->total_leads  > 0 ? round($row->enrolled / $row->total_leads * 100, 1) . '%' : '0%',
        ]);

        return [
            'title'         => 'Source Effectiveness Report',
            'headings'      => ['Source', 'Leads', 'Applied', 'Offered', 'Enrolled', 'Lead→Apply %', 'Apply→Enrol %', 'Overall %'],
            'rows'          => $rows,
            'filterDetails' => $this->dateFilterDetails($filters),
        ];
    }

    /** @return array{title: string, headings: array, rows: Collection, filterDetails: array} */
    private function lostLeadAnalysisData(array $scope, array $filters): array
    {
        $raw = $this->reportService->lostLeadAnalysisForExport($scope, $filters);

        $rows = $raw->map(fn ($lead) => [
            $lead->status_changed_at?->format('d/m/Y'),
            trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')),
            $lead->mobile,
            $lead->source,
            $lead->lost_reason,
            $lead->campus?->name,
            $lead->assignedCounsellor?->name,
            $lead->status_changed_at && $lead->created_at
                ? (int) $lead->created_at->diffInDays($lead->status_changed_at)
                : null,
        ]);

        return [
            'title'         => 'Lost Lead Analysis',
            'headings'      => ['Date Lost', 'Name', 'Mobile', 'Source', 'Lost Reason', 'Campus', 'Counsellor', 'Days to Loss'],
            'rows'          => $rows,
            'filterDetails' => $this->dateFilterDetails($filters),
        ];
    }

    /** @return array{title: string, headings: array, rows: Collection, filterDetails: array} */
    private function feeCollectionData(array $scope, array $filters): array
    {
        $raw = $this->reportService->feeCollectionForExport($scope, $filters);

        $rows = $raw->map(fn ($tx) => [
            $tx->attempted_at?->format('d/m/Y'),
            trim(($tx->lead?->first_name ?? '') . ' ' . ($tx->lead?->last_name ?? '')),
            $tx->application?->programme?->name,
            $tx->fee_type?->value ?? $tx->fee_type,
            number_format((float) $tx->amount, 2),
            $tx->status?->value ?? $tx->status,
            $tx->gateway_order_id ?? $tx->gateway_payment_id,
            $tx->lead?->assignedCounsellor?->name,
        ]);

        return [
            'title'         => 'Fee Collection Report',
            'headings'      => ['Date', 'Student', 'Programme', 'Fee Type', 'Amount', 'Status', 'Gateway Order ID', 'Counsellor'],
            'rows'          => $rows,
            'filterDetails' => $this->dateFilterDetails($filters),
        ];
    }

    /** @return array{title: string, headings: array, rows: Collection, filterDetails: array} */
    private function documentComplianceData(array $scope, array $filters): array
    {
        $raw = $this->reportService->documentComplianceForExport($scope, $filters);

        $rows = $raw->map(fn ($app) => [
            $app->submitted_at?->format('d/m/Y'),
            trim(($app->lead?->first_name ?? '') . ' ' . ($app->lead?->last_name ?? '')),
            $app->programme?->name,
            $app->campus?->name,
            (int) $app->total_docs,
            (int) $app->verified_docs,
            (int) $app->pending_docs,
            (int) $app->rejected_docs,
            (int) $app->missing_docs,
        ]);

        return [
            'title'         => 'Document Compliance Report',
            'headings'      => ['Submitted', 'Applicant', 'Programme', 'Campus', 'Total Docs', 'Verified', 'Pending', 'Rejected', 'Missing'],
            'rows'          => $rows,
            'filterDetails' => $this->dateFilterDetails($filters),
        ];
    }

    /** @return array{title: string, headings: array, rows: Collection, filterDetails: array} */
    private function yearOnYearData(array $scope, array $filters): array
    {
        $raw = $this->reportService->yearOnYearBreakdown($scope, $filters);

        $year     = (int) ($filters['year'] ?? now()->year);
        $prevYear = $year - 1;

        $rows = $raw->map(fn ($row) => [
            $row->label,
            (int) $row->current_leads,
            (int) $row->prev_leads,
            (int) $row->current_leads - (int) $row->prev_leads,
            (int) $row->current_applied,
            (int) $row->prev_applied,
            (int) $row->current_enrolled,
            (int) $row->prev_enrolled,
        ]);

        return [
            'title'         => "Year-on-Year: {$year} vs {$prevYear}",
            'headings'      => ['Dimension', "Leads ({$year})", "Leads ({$prevYear})", 'Δ Leads', "Applied ({$year})", "Applied ({$prevYear})", "Enrolled ({$year})", "Enrolled ({$prevYear})"],
            'rows'          => $rows,
            'filterDetails' => ['Year' => (string) $year, 'Group By' => $filters['group_by'] ?? 'programme'],
        ];
    }

    /** @return array{title: string, headings: array, rows: Collection, filterDetails: array} */
    private function agentPerformanceData(array $scope, array $filters): array
    {
        $raw = $this->reportService->agentPerformance($scope, $filters);

        $rows = $raw->map(fn ($agent) => [
            $agent->name,
            $agent->email,
            is_object($agent->status) ? $agent->status->value : $agent->status,
            (int) $agent->leads_referred,
            (int) $agent->applied,
            (int) $agent->enrolled,
            $agent->leads_referred > 0 ? round($agent->enrolled / $agent->leads_referred * 100, 1) . '%' : '0%',
            number_format((float) $agent->commission_accrued, 2),
            number_format((float) $agent->commission_paid, 2),
        ]);

        return [
            'title'         => 'Agent Performance Report',
            'headings'      => ['Agent', 'Email', 'Status', 'Leads Referred', 'Applied', 'Enrolled', 'Conversion %', 'Commission Accrued', 'Commission Paid'],
            'rows'          => $rows,
            'filterDetails' => $this->dateFilterDetails($filters),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function filename(string $reportType, array $filters): string
    {
        $date = now()->format('Ymd');
        $slug = Str::slug($reportType);

        return "crm-report-{$slug}-{$date}";
    }

    private function filterSummary(array $filters): string
    {
        $from = $filters['from'] ?? null;
        $to   = $filters['to']   ?? null;

        if ($from && $to) {
            return \Carbon\Carbon::parse($from)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($to)->format('d M Y');
        }
        if ($filters['year'] ?? null) {
            return 'Year: ' . $filters['year'];
        }

        return '';
    }

    /** @return array<string, string|null> */
    private function dateFilterDetails(array $filters): array
    {
        return [
            'From' => $filters['from'] ?? null,
            'To'   => $filters['to']   ?? null,
        ];
    }
}
