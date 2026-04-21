<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Tasks;

use App\Models\CRM\Institution;
use App\Models\CRM\Tasks\TaskEscalationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskEscalationRule>
 */
class TaskEscalationRuleFactory extends Factory
{
    protected $model = TaskEscalationRule::class;

    public function definition(): array
    {
        return [
            'institution_id'         => Institution::factory(),
            'overdue_threshold_hours' => fake()->numberBetween(1, 48),
            'escalate_to_role_id'    => null,
            'notification_channel'   => 'in_app',
            'is_active'              => true,
        ];
    }
}
