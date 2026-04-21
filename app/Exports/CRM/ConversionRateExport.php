<?php

declare(strict_types=1);

namespace App\Exports\CRM;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

// BRD: CRM-AP-019 — Conversion rate report export
class ConversionRateExport implements FromCollection, WithHeadings
{
    public function __construct(protected Collection $stats) {}

    public function collection(): Collection
    {
        return $this->stats->map(fn ($row) => [
            'Programme'          => $row['programme_name'] ?? '-',
            'Batch'              => $row['batch'] ?? '-',
            'Source'             => $row['source'] ?? '-',
            'Counsellor'         => $row['counsellor_name'] ?? '-',
            'Total Applications' => $row['total_applications'],
            'Enrolled'           => $row['enrolled_count'],
            'Conversion Rate %'  => $row['conversion_rate'],
        ]);
    }

    public function headings(): array
    {
        return ['Programme', 'Batch', 'Source', 'Counsellor', 'Total Applications', 'Enrolled', 'Conversion Rate %'];
    }
}
