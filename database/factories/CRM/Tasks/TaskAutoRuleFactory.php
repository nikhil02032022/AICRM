<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Tasks;

use App\Enums\CRM\Tasks\TaskType;
use App\Models\CRM\Institution;
use App\Models\CRM\Tasks\TaskAutoRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAutoRule>
 */
class TaskAutoRuleFactory extends Factory
{
    protected $model = TaskAutoRule::class;

    public function definition(): array
    {
        return [
            'institution_id'             => Institution::factory(),
            'trigger_type'               => 'inactivity',
            'inactivity_threshold_hours' => fake()->numberBetween(24, 168),
            'task_type'                  => fake()->randomElement(TaskType::cases())->value,
            'priority'                   => 'normal',
            'assignee_strategy'          => 'lead_owner',
            'is_active'                  => true,
        ];
    }
}
