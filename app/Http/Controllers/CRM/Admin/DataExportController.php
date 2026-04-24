<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Http\Controllers\Controller;
use App\Services\CRM\Admin\DataExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// BRD: CRM-SA-005 — Data export (leads, applications, contacts)
final class DataExportController extends Controller
{
    public function __construct(private readonly DataExportService $service) {}

    public function index(): View
    {
        $this->authorize('crm.admin.data-export.manage');

        return view('crm.admin.data-export.index');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('crm.admin.data-export.manage');

        $validated = $request->validate([
            'entity' => 'required|in:leads,applications,contacts',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        $institutionId = $request->user()->institution_id;
        $filters       = array_filter([
            'from'   => $validated['from'] ?? null,
            'to'     => $validated['to'] ?? null,
            'status' => $validated['status'] ?? null,
        ]);

        return match ($validated['entity']) {
            'leads' => $this->service->exportLeads($institutionId, $filters),
            default => abort(422, 'Entity export not yet implemented.'),
        };
    }
}
