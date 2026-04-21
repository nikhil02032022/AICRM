<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Payments\StoreRefundRequest;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\CRM\Payments\RefundRequest;
use App\Services\CRM\Payments\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-FM-011 — Web UI for refund request and approval workflow.
class RefundController extends Controller
{
    public function __construct(private readonly RefundService $service) {}

    public function index(): View
    {
        Gate::authorize('payments.view');
        $refunds = RefundRequest::query()->with('transaction')->latest()->paginate(20);

        return view('crm.payments.refunds.index', ['refunds' => $refunds]);
    }

    public function store(StoreRefundRequest $request, PaymentTransaction $transaction): RedirectResponse
    {
        $data = $request->validated();
        $this->service->request($transaction, (string) $data['reason'], (float) $data['amount']);

        return redirect()->route('crm.payments.refunds.index')
            ->with('status', 'Refund requested.');
    }

    public function managerApprove(RefundRequest $refundRequest): RedirectResponse
    {
        Gate::authorize('payments.refund.approve');
        $this->service->managerApprove($refundRequest);

        return back()->with('status', 'Approved by manager.');
    }

    public function financeApprove(RefundRequest $refundRequest): RedirectResponse
    {
        Gate::authorize('payments.refund.approve');
        $this->service->financeApprove($refundRequest);

        return back()->with('status', 'Finance approved; gateway refund queued.');
    }

    public function reject(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        Gate::authorize('payments.refund.approve');
        $reason = (string) $request->input('reason', '');
        $this->service->reject($refundRequest, $reason);

        return back()->with('status', 'Refund rejected.');
    }
}
