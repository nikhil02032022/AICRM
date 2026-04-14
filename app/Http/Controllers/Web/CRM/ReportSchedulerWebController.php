<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\ReportFormat;
use App\Enums\CRM\ReportFrequency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreReportScheduleRequest;
use App\Models\CRM\CustomReport;
use App\Models\CRM\ReportSchedule;
use App\Services\CRM\Analytics\ReportSchedulerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-AR-020 — Web controller: scheduled report delivery
final class ReportSchedulerWebController extends Controller
{
    public function __construct(
        private readonly ReportSchedulerService $service,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.reports.manage');

        $schedules = $this->service->paginate(
            $request->user()->institution_id,
            $request->only(['search']),
        );

        return view('crm.analytics.report-scheduler.index', [
            'schedules'        => $schedules,
            'frequencyOptions' => ReportFrequency::optionsForSelect(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('crm.reports.manage');

        $reports = CustomReport::select(['id', 'uuid', 'name'])->get();

        return view('crm.analytics.report-scheduler.create', [
            'reports'          => $reports,
            'frequencyOptions' => ReportFrequency::optionsForSelect(),
            'formatOptions'    => ReportFormat::optionsForSelect(),
        ]);
    }

    public function store(StoreReportScheduleRequest $request): RedirectResponse
    {
        Gate::authorize('crm.reports.manage');

        $this->service->create(
            $request->validated(),
            $request->user()->institution_id,
            $request->user()->id,
        );

        return redirect()
            ->route('crm.reports.scheduler.index')
            ->with('success', 'Report schedule created. First delivery queued automatically.');
    }

    public function edit(ReportSchedule $reportSchedule): View
    {
        Gate::authorize('crm.reports.manage');

        $reports = CustomReport::select(['id', 'uuid', 'name'])->get();

        return view('crm.analytics.report-scheduler.create', [
            'schedule'         => $reportSchedule,
            'reports'          => $reports,
            'frequencyOptions' => ReportFrequency::optionsForSelect(),
            'formatOptions'    => ReportFormat::optionsForSelect(),
        ]);
    }

    public function update(StoreReportScheduleRequest $request, ReportSchedule $reportSchedule): RedirectResponse
    {
        Gate::authorize('crm.reports.manage');

        $this->service->update($reportSchedule, $request->validated());

        return redirect()
            ->route('crm.reports.scheduler.index')
            ->with('success', 'Schedule updated.');
    }

    public function destroy(ReportSchedule $reportSchedule): RedirectResponse
    {
        Gate::authorize('crm.reports.manage');

        $this->service->delete($reportSchedule);

        return redirect()
            ->route('crm.reports.scheduler.index')
            ->with('success', 'Schedule deleted.');
    }

    // BRD: CRM-AR-020 — Manually trigger delivery for a schedule
    public function dispatch(ReportSchedule $reportSchedule): JsonResponse
    {
        Gate::authorize('crm.reports.manage');

        $delivery = $this->service->dispatchDelivery($reportSchedule);

        return response()->json([
            'success' => true,
            'data'    => ['delivery_uuid' => $delivery->uuid],
            'message' => 'Report delivery queued.',
        ]);
    }
}
