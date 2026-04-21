<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Payments\StoreFeeStructureRequest;
use App\Http\Requests\CRM\Payments\UpdateFeeStructureRequest;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Payments\FeeStructure;
use App\Services\CRM\Payments\FeeStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-FM-001, CRM-FM-002 — Fee structure management (web UI)
class FeeStructureController extends Controller
{
    public function __construct(private readonly FeeStructureService $service) {}

    public function index(): View
    {
        Gate::authorize('fee_structure.manage');
        $items = FeeStructure::query()->with('programme')->orderByDesc('id')->paginate(20);
        $programmes = CrmProgramme::query()->orderBy('name')->get(['id', 'name']);

        return view('crm.payments.fee_structures.index', [
            'items' => $items,
            'programmes' => $programmes,
        ]);
    }

    public function store(StoreFeeStructureRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('crm.payments.fee-structures.index')
            ->with('status', 'Fee structure created.');
    }

    public function update(UpdateFeeStructureRequest $request, FeeStructure $feeStructure): RedirectResponse
    {
        $this->service->update($feeStructure, $request->validated());

        return redirect()->route('crm.payments.fee-structures.index')
            ->with('status', 'Fee structure updated.');
    }

    public function toggle(FeeStructure $feeStructure): RedirectResponse
    {
        Gate::authorize('fee_structure.manage');
        $this->service->toggle($feeStructure);

        return redirect()->route('crm.payments.fee-structures.index')
            ->with('status', 'Fee structure status toggled.');
    }
}
