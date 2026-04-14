<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\ReportEntity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreCustomReportRequest;
use App\Models\CRM\CustomReport;
use App\Services\CRM\Analytics\CustomReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-AR-018 — Web controller: custom report builder
final class CustomReportWebController extends Controller
{
    public function __construct(
        private readonly CustomReportService $service,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CustomReport::class);

        $reports = $this->service->paginate(
            $request->only(['entity', 'search']),
        );

        return view('crm.analytics.custom-reports.index', [
            'reports'       => $reports,
            'entityOptions' => ReportEntity::optionsForSelect(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', CustomReport::class);

        return view('crm.analytics.custom-reports.create', [
            'entityOptions'    => ReportEntity::optionsForSelect(),
            'operatorOptions'  => ['=' => 'Equals', '!=' => 'Not equals', 'like' => 'Contains', '<' => 'Less than', '>' => 'Greater than'],
        ]);
    }

    public function store(StoreCustomReportRequest $request): RedirectResponse
    {
        Gate::authorize('create', CustomReport::class);

        $report = $this->service->create(
            $request->validated(),
            $request->user()->institution_id,
            $request->user()->id,
        );

        return redirect()
            ->route('crm.reports.custom.show', $report->uuid)
            ->with('success', 'Report created successfully.');
    }

    public function show(CustomReport $customReport, Request $request): View
    {
        Gate::authorize('view', $customReport);

        $result = null;
        if ($request->query('run') === '1') {
            $result = $this->service->run($customReport, perPage: 500);
        }

        return view('crm.analytics.custom-reports.show', [
            'report' => $customReport,
            'result' => $result,
        ]);
    }

    public function edit(CustomReport $customReport): View
    {
        Gate::authorize('update', $customReport);

        return view('crm.analytics.custom-reports.create', [
            'report'           => $customReport,
            'entityOptions'    => ReportEntity::optionsForSelect(),
            'operatorOptions'  => ['=' => 'Equals', '!=' => 'Not equals', 'like' => 'Contains', '<' => 'Less than', '>' => 'Greater than'],
        ]);
    }

    public function update(StoreCustomReportRequest $request, CustomReport $customReport): RedirectResponse
    {
        Gate::authorize('update', $customReport);

        $this->service->update($customReport, $request->validated());

        return redirect()
            ->route('crm.reports.custom.show', $customReport->uuid)
            ->with('success', 'Report updated.');
    }

    public function destroy(CustomReport $customReport): RedirectResponse
    {
        Gate::authorize('delete', $customReport);

        $this->service->delete($customReport);

        return redirect()
            ->route('crm.reports.custom.index')
            ->with('success', 'Report deleted.');
    }

    // BRD: CRM-AR-018 — Run report and return JSON rows for client-side table rendering
    public function run(CustomReport $customReport): JsonResponse
    {
        Gate::authorize('view', $customReport);

        $result = $this->service->run($customReport);

        return response()->json([
            'success' => true,
            'data'    => $result,
            'message' => "Report ran — {$result['total']} rows.",
        ]);
    }
}
