<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web;

use App\Http\Requests\CRM\ErpConversion\TriggerErpConversionRequest;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Repositories\CRM\Application\ApplicationConversionLogRepositoryInterface;
use App\Services\CRM\Erp\ErpConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-AP-016 — Staff-facing web controller for ERP conversion operations
final class ErpConversionController
{
    public function __construct(
        private readonly ErpConversionService $conversionService,
        private readonly ApplicationConversionLogRepositoryInterface $conversionLogRepository,
    ) {}

    /**
     * List all ERP conversion logs.
     * GET /crm/conversions
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ApplicationConversionLog::class);

        $filters = $request->only(['status', 'from_date', 'to_date']);
        $logs = $this->conversionLogRepository->paginate($filters, 20);

        return view('crm.conversions.index', compact('logs', 'filters'));
    }

    /**
     * Show a single conversion log.
     * GET /crm/conversions/{log:uuid}
     */
    public function show(ApplicationConversionLog $log): View
    {
        Gate::authorize('view', $log);

        $log->load(['application', 'lead', 'convertedBy']);

        return view('crm.conversions.show', compact('log'));
    }

    /**
     * Trigger ERP conversion for an application.
     * POST /crm/applications/{application:uuid}/convert
     */
    public function trigger(TriggerErpConversionRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('convert', $application);

        $this->conversionService->convert($application, (int) $request->user()->id);

        return redirect()
            ->route('crm.applications.show', $application->uuid)
            ->with('success', 'ERP conversion queued. The applicant will be enrolled once confirmed.');
    }

    /**
     * Retry a failed conversion log.
     * POST /crm/conversions/{log:uuid}/retry
     */
    public function retry(ApplicationConversionLog $log): RedirectResponse
    {
        Gate::authorize('retry', $log);

        $this->conversionService->retry($log);

        return redirect()
            ->route('crm.conversions.show', $log->uuid)
            ->with('success', 'ERP conversion retry queued.');
    }
}
