<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use App\Models\CRM\Institution;
use App\Models\CRM\Payments\FeeInstallmentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeeInstallmentPlan> */
class FeeInstallmentPlanFactory extends Factory
{
    protected $model = FeeInstallmentPlan::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'name'           => 'Two-part Plan',
            'fee_type'       => FeeType::TUITION_ADVANCE->value,
            'total_amount'   => 100000.00,
            'schedule'       => [
                ['sequence' => 1, 'percent' => 50, 'due_offset_days' => 0,  'label' => 'On booking'],
                ['sequence' => 2, 'percent' => 50, 'due_offset_days' => 30, 'label' => '30 days later'],
            ],
            'is_active' => true,
        ];
    }
}
