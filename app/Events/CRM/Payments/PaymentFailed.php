<?php

declare(strict_types=1);

namespace App\Events\CRM\Payments;

use App\Models\CRM\Payments\PaymentTransaction;
use Illuminate\Foundation\Events\Dispatchable;

// BRD: CRM-FM-005 — Fired when a payment transaction fails.
class PaymentFailed
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentTransaction $transaction,
        public readonly ?string $reason = null,
    ) {}
}
