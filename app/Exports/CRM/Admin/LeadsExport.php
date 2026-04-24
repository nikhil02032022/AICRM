<?php

declare(strict_types=1);

namespace App\Exports\CRM\Admin;

use App\Models\CRM\Lead;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

// BRD: CRM-SA-005 — Export leads to Excel
class LeadsExport implements FromQuery, WithHeadings, WithMapping
{
    /** @param array<string,mixed> $filters */
    public function __construct(
        private readonly int $institutionId,
        private readonly array $filters = [],
    ) {}

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return Lead::withoutGlobalScopes()
            ->where('institution_id', $this->institutionId)
            ->when($this->filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($this->filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($this->filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at');
    }

    /** @return array<int,string> */
    public function headings(): array
    {
        return ['UUID', 'First Name', 'Last Name', 'Mobile', 'Email', 'Source', 'Status', 'Score', 'Assigned Counsellor', 'Created At'];
    }

    /** @param Lead $row */
    public function map($row): array
    {
        return [
            $row->uuid,
            $row->first_name,
            $row->last_name,
            $row->mobile,
            $row->email,
            $row->source?->label() ?? $row->source,
            $row->status?->label() ?? $row->status,
            $row->lead_score,
            $row->assignedCounsellor?->name ?? '—',
            $row->created_at?->format('d M Y H:i'),
        ];
    }
}
