<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ScholarshipType;
use App\Models\CRM\Institution;
use App\Models\CRM\Scholarships\ScholarshipCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ScholarshipCategory> */
class ScholarshipCategoryFactory extends Factory
{
    protected $model = ScholarshipCategory::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'code'           => 'SC_'.Str::upper(Str::random(6)),
            'name'           => $this->faker->words(2, true),
            'type'           => ScholarshipType::MERIT->value,
            'computation'    => 'percent',
            'value'          => 10.00,
            'max_cap'        => 25000.00,
            'is_active'      => true,
        ];
    }
}
