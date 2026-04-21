<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Payments\ApplyInstallmentPlanRequest;
use App\Models\CRM\Application;
use App\Models\CRM\Payments\FeeInstallmentPlan;
use App\Services\CRM\Payments\ApplicationInstallmentService;
use Illuminate\Http\RedirectResponse;

// BRD: CRM-FM-009
class ApplicationInstallmentController extends Controller
{
    public function __construct(private readonly ApplicationInstallmentService $service) {}

    public function apply(ApplyInstallmentPlanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $application = Application::where('uuid', $data['application_uuid'])->firstOrFail();
        $plan = FeeInstallmentPlan::findOrFail($data['plan_id']);

        $this->service->applyPlan($application, $plan);

        return back()->with('status', 'Installment plan applied to application.');
    }
}
