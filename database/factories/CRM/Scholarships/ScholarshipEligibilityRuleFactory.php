<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Scholarships;

use App\Models\CRM\Institution;
use App\Models\CRM\Scholarships\ScholarshipCategory;
use App\Models\CRM\Scholarships\ScholarshipEligibilityRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ScholarshipEligibilityRule> */
class ScholarshipEligibilityRuleFactory extends Factory
{
    protected $model = ScholarshipEligibilityRule::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'scholarship_category_id' => ScholarshipCategory::factory(),
            'attribute'  => 'application.entrance_score',
            'operator'   => 'gte',
            'value'      => 85,
            'combinator' => 'AND',
            'sort_order' => 0,
        ];
    }
}
