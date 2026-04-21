<?php

declare(strict_types=1);

namespace App\Exports\CRM;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

// BRD: CRM-FM-012 — Fee dashboard export
class FeeCollectionExport implements FromArray, WithHeadings
{
    /**
     * @param array<string,mixed> $data
     */
    public function __construct(private readonly array $data) {}

    /** @return array<int,array<int,mixed>> */
    public function array(): array
    {
        $rows = [];
        $rows[] = ['Collected', $this->data['summary']['collected'] ?? 0];
        $rows[] = ['Pending', $this->data['summary']['pending'] ?? 0];
        $rows[] = ['Refunded', $this->data['summary']['refunded'] ?? 0];
        $rows[] = ['Refunds Requested', $this->data['summary']['refunds_requested'] ?? 0];
        $rows[] = ['Forecast (open)', $this->data['forecast'] ?? 0];

        return $rows;
    }

    /** @return array<int,string> */
    public function headings(): array
    {
        return ['Metric', 'Amount'];
    }
}
