<?php

declare(strict_types=1);

namespace App\Exports\CRM\Analytics;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// BRD: CRM-AR-019 — Single export class for all 9 standard reports; column mapping is per report type
final class StandardReportExport implements
    FromCollection,
    WithHeadings,
    WithTitle,
    ShouldAutoSize,
    WithStyles
{
    /**
     * @param string $reportType  slug matching known report types (enquiry-register, counsellor-activity, …)
     * @param Collection<int, mixed> $rows  already-normalised rows returned by ReportExportService
     * @param string $title  worksheet title
     */
    public function __construct(
        private readonly string     $reportType,
        private readonly Collection $rows,
        private readonly string     $title,
    ) {}

    /** @return Collection<int, array<int, mixed>> */
    public function collection(): Collection
    {
        return $this->rows;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return match ($this->reportType) {
            'enquiry-register'   => ['Date', 'Name', 'Mobile', 'Email', 'Source', 'Status', 'Campus', 'Programme', 'Counsellor'],
            'counsellor-activity' => ['Counsellor', 'Campus', 'New Leads', 'Converted', 'Conversion %', 'Tasks Completed', 'Tasks Overdue', 'Calls Made', 'Sessions'],
            'application-status' => ['Submitted', 'Applicant', 'Mobile', 'Programme', 'Campus', 'Status', 'Counsellor'],
            'source-effectiveness' => ['Source', 'Leads', 'Applied', 'Offered', 'Enrolled', 'Lead→Apply %', 'Apply→Enrol %', 'Overall %'],
            'lost-lead-analysis' => ['Date Lost', 'Name', 'Mobile', 'Source', 'Lost Reason', 'Campus', 'Counsellor', 'Days to Loss'],
            'fee-collection'     => ['Date', 'Student', 'Programme', 'Fee Type', 'Amount', 'Status', 'Gateway Order ID', 'Counsellor'],
            'document-compliance' => ['Submitted', 'Applicant', 'Programme', 'Campus', 'Total Docs', 'Verified', 'Pending', 'Rejected', 'Missing'],
            'year-on-year'       => ['Dimension', 'Leads (Current)', 'Leads (Prev)', 'Δ Leads', 'Applied (Current)', 'Applied (Prev)', 'Enrolled (Current)', 'Enrolled (Prev)'],
            'agent-performance'  => ['Agent', 'Email', 'Status', 'Leads Referred', 'Applied', 'Enrolled', 'Conversion %', 'Commission Accrued', 'Commission Paid'],
            default              => [],
        };
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31); // Excel sheet name limit is 31 chars
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
