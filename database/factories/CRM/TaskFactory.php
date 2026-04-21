<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Enums\CRM\Tasks\TaskDisposition;
use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskSource;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Enums\CRM\Tasks\TaskType;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'lead_id'        => Lead::factory(),
            'assigned_to'    => User::factory(),
            'created_by'     => User::factory(),
            'title'          => fake()->sentence(4),
            'description'    => fake()->optional()->sentence(),
            'type'           => fake()->randomElement(TaskType::cases())->value,
            'priority'       => TaskPriority::Normal->value,
            'status'         => TaskStatus::Pending->value,
            'source'         => TaskSource::Manual->value,
            'due_at'         => now()->addDays(fake()->numberBetween(1, 7)),
        ];
    }

    public function overdue(): static
    {
        return $this->state([
            'status' => TaskStatus::Overdue->value,
            'due_at' => now()->subDay(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status'       => TaskStatus::Completed->value,
            'completed_at' => now(),
            'disposition'  => TaskDisposition::ReachedInterested->value,
        ]);
    }

    public function auto(): static
    {
        return $this->state([
            'source' => TaskSource::Auto->value,
        ]);
    }
}
