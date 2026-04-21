<?php

declare(strict_types=1);

namespace App\Exports\CRM;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConversionReportExport implements FromCollection, WithHeadings
{
    public function __construct(protected Collection $stats)
    {
    }

    public function collection(): Collection
    {
        return $this->stats->map(function ($row) {
            return [
                'Programme' => $row['programme_name'] ?? '-',
                'Source' => $row['source'] ?? '-',
                'Counsellor' => $row['counsellor_name'] ?? '-',
                'Conversions' => $row['conversions'],
                'From' => $row['from_date'],
                'To' => $row['to_date'],
            ];
        });
    }

    public function headings(): array
    {
        return ['Programme', 'Source', 'Counsellor', 'Conversions', 'From', 'To'];
    }
}
