<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Payments;

use App\Enums\CRM\Payments\FeeType;
use App\Enums\CRM\Payments\GatewayProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Payments\InitiateFeeCollectionRequest;
use App\Models\CRM\Application;
use App\Services\CRM\Payments\FeeCollectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-FM-001, CRM-FM-002, CRM-FM-005 — Counsellor-facing payment collection UI.
class PaymentController extends Controller
{
    public function __construct(private readonly FeeCollectionService $fees) {}

    public function show(Application $application): View
    {
        Gate::authorize('payments.view');
        $application->load(['programme', 'lead', 'transactions' => fn ($q) => $q->latest()]);

        return view('crm.payments.application_fee_panel', [
            'application'  => $application,
            'transactions' => $application->transactions,
        ]);
    }

    public function initiate(InitiateFeeCollectionRequest $request, Application $application): RedirectResponse
    {
        $feeType = FeeType::from($request->validated()['fee_type']);
        $gateway = isset($request->validated()['gateway'])
            ? GatewayProvider::from($request->validated()['gateway'])
            : null;

        $txn = $this->fees->initiate($application, $feeType, $gateway);

        return redirect()->route('crm.applications.show', $application->uuid)
            ->with('status', 'Payment initiated (txn '.$txn->uuid.').');
    }
}
