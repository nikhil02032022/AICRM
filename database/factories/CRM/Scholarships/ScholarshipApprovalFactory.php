<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ApprovalStage;
use App\Models\CRM\Institution;
use App\Models\CRM\Scholarships\ScholarshipApproval;
use App\Models\CRM\Scholarships\ScholarshipAward;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ScholarshipApproval> */
class ScholarshipApprovalFactory extends Factory
{
    protected $model = ScholarshipApproval::class;

    public function definition(): array
    {
        return [
            'institution_id'       => Institution::factory(),
            'scholarship_award_id' => ScholarshipAward::factory(),
            'stage'                => ApprovalStage::MANAGER->value,
            'decision'             => 'approved',
            'actor_id'             => User::factory(),
            'comment'              => null,
            'acted_at'             => now(),
        ];
    }
}
