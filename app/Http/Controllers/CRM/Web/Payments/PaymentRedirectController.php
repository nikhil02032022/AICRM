<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Payments;

use App\Http\Controllers\Controller;
use App\Models\CRM\Payments\PaymentLink;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// BRD: CRM-FM-004 — Resolve signed payment-link token and redirect to the gateway checkout.
class PaymentRedirectController extends Controller
{
    public function show(string $token): RedirectResponse
    {
        $link = PaymentLink::withoutGlobalScopes()
            ->where('token', $token)
            ->first();

        if ($link === null || ($link->expires_at && $link->expires_at->isPast())) {
            throw new NotFoundHttpException('Payment link not found or expired.');
        }

        if ($link->opened_at === null) {
            $link->opened_at = now();
            $link->save();
        }

        $transaction = $link->transaction()->withoutGlobalScopes()->first();

        // Minimal redirect: in production a checkout page would be served. For now,
        // bounce to a placeholder route the gateway/checkout SDK will replace.
        return redirect()->route('crm.payments.checkout', [
            'transaction' => $transaction?->uuid,
        ]);
    }

    public function checkout(string $transaction)
    {
        return response()->view('crm.payments.checkout_placeholder', [
            'transaction_uuid' => $transaction,
        ]);
    }
}
