<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ApprovalStage;
use App\Enums\CRM\Scholarships\ScholarshipAwardStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\Scholarships\ScholarshipAward;
use App\Models\CRM\Scholarships\ScholarshipCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ScholarshipAward> */
class ScholarshipAwardFactory extends Factory
{
    protected $model = ScholarshipAward::class;

    public function definition(): array
    {
        return [
            'institution_id'          => Institution::factory(),
            'application_uuid'        => Str::uuid(),
            'lead_uuid'               => Str::uuid(),
            'scholarship_category_id' => ScholarshipCategory::factory(),
            'amount'                  => 10000.00,
            'status'                  => ScholarshipAwardStatus::DRAFT->value,
            'current_stage'           => ApprovalStage::COUNSELLOR->value,
        ];
    }
}
