<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Payments\StoreFeeInstallmentPlanRequest;
use App\Http\Requests\CRM\Payments\UpdateFeeInstallmentPlanRequest;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Payments\FeeInstallmentPlan;
use App\Services\CRM\Payments\FeeInstallmentPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-FM-009
class FeeInstallmentPlanController extends Controller
{
    public function __construct(private readonly FeeInstallmentPlanService $service) {}

    public function index(): View
    {
        Gate::authorize('installment.plan.manage');
        $plans = FeeInstallmentPlan::query()->with('programme')->orderByDesc('id')->paginate(20);
        $programmes = CrmProgramme::query()->orderBy('name')->get(['id', 'name']);

        return view('crm.payments.installments.index', [
            'plans' => $plans,
            'programmes' => $programmes,
        ]);
    }

    public function store(StoreFeeInstallmentPlanRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('crm.payments.installments.index')->with('status', 'Installment plan created.');
    }

    public function update(UpdateFeeInstallmentPlanRequest $request, FeeInstallmentPlan $plan): RedirectResponse
    {
        $this->service->update($plan, $request->validated());

        return redirect()->route('crm.payments.installments.index')->with('status', 'Installment plan updated.');
    }

    public function toggle(FeeInstallmentPlan $plan): RedirectResponse
    {
        Gate::authorize('installment.plan.manage');
        $this->service->toggle($plan);

        return redirect()->route('crm.payments.installments.index')->with('status', 'Plan toggled.');
    }
}
