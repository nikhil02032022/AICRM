<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Payments\FeeStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeeStructure> */
class FeeStructureFactory extends Factory
{
    protected $model = FeeStructure::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'programme_id'   => CrmProgramme::factory(),
            'fee_type'       => FeeType::APPLICATION->value,
            'amount'         => 1500.00,
            'currency'       => 'INR',
            'is_active'      => true,
        ];
    }
}
