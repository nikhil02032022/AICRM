<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Payments;

use App\Exports\CRM\FeeCollectionExport;
use App\Http\Controllers\Controller;
use App\Services\CRM\Payments\FeeDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-FM-012 — Finance dashboard
class FeeDashboardController extends Controller
{
    public function __construct(private readonly FeeDashboardService $dashboard) {}

    public function index(Request $request): View|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        Gate::authorize('fee_dashboard.view');

        $filters = $request->only(['from', 'to']);
        $data    = $this->dashboard->compose($filters);

        if ($request->get('export') === 'xlsx') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new FeeCollectionExport($data),
                'fee_dashboard.xlsx',
                \Maatwebsite\Excel\Excel::XLSX,
            );
        }

        return view('crm.payments.fee_dashboard', [
            'data'    => $data,
            'filters' => $filters,
        ]);
    }
}
