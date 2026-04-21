<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Enums\CRM\ActivityType;
use App\Models\CRM\Activity;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        $institution = Institution::factory()->create();

        return [
            'institution_id' => $institution->id,
            'subject_type'   => Lead::class,
            'subject_id'     => Lead::factory()->for($institution),
            'type'           => ActivityType::TASK_CREATED->value,
            'performed_by_id' => User::factory()->for($institution),
            'body'           => fake()->optional()->sentence(),
            'metadata'       => [],
        ];
    }
}
