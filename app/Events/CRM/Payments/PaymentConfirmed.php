<?php

declare(strict_types=1);

namespace App\Events\CRM\Payments;

use App\Models\CRM\Payments\PaymentTransaction;
use Illuminate\Foundation\Events\Dispatchable;

// BRD: CRM-FM-005 — Fired when a payment transaction is captured/successful.
class PaymentConfirmed
{
    use Dispatchable;

    public function __construct(public readonly PaymentTransaction $transaction) {}
}
