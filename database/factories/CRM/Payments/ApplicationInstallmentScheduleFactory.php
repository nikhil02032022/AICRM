<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Payments;

use App\Enums\CRM\Payments\InstallmentStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\Payments\ApplicationInstallmentSchedule;
use App\Models\CRM\Payments\FeeInstallmentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ApplicationInstallmentSchedule> */
class ApplicationInstallmentScheduleFactory extends Factory
{
    protected $model = ApplicationInstallmentSchedule::class;

    public function definition(): array
    {
        return [
            'institution_id'          => Institution::factory(),
            'application_uuid'        => Str::uuid(),
            'fee_installment_plan_id' => FeeInstallmentPlan::factory(),
            'sequence'                => 1,
            'label'                   => 'On booking',
            'amount'                  => 50000.00,
            'due_date'                => now()->toDateString(),
            'status'                  => InstallmentStatus::PENDING->value,
        ];
    }
}
