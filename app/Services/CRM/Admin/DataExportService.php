<?php

declare(strict_types=1);

namespace App\Services\CRM\Admin;

use App\Exports\CRM\Admin\LeadsExport;
use App\Models\CRM\Lead;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// BRD: CRM-SA-005 — Data export (leads, applications, contacts)
class DataExportService
{
    public function exportLeads(int $institutionId, array $filters = []): BinaryFileResponse
    {
        return Excel::download(
            new LeadsExport($institutionId, $filters),
            'leads-export-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
