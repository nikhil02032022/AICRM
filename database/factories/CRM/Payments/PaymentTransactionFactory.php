<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use App\Enums\CRM\Payments\GatewayProvider;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\Payments\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PaymentTransaction> */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'institution_id'   => Institution::factory(),
            'application_uuid' => Str::uuid(),
            'lead_uuid'        => Str::uuid(),
            'fee_type'         => FeeType::APPLICATION->value,
            'gateway'          => GatewayProvider::RAZORPAY->value,
            'amount'           => 1500.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING->value,
            'idempotency_key'  => 'fc_'.Str::random(24),
            'attempted_at'     => now(),
        ];
    }
}
