<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\Task;
use App\Models\CRM\Tasks\TaskAutoRule;
use App\Models\CRM\Tasks\TaskEscalationRule;
use App\Observers\CRM\AuditObserver;
use App\Observers\CRM\Tasks\TaskObserver;
use App\Policies\CRM\Tasks\Manager\TeamTaskPolicy;
use App\Policies\CRM\Tasks\TaskPolicy;
use App\Repositories\CRM\Tasks\EloquentTaskAutoRuleRepository;
use App\Repositories\CRM\Tasks\EloquentTaskEscalationRuleRepository;
use App\Repositories\CRM\Tasks\EloquentTaskRepository;
use App\Repositories\CRM\Tasks\TaskAutoRuleRepositoryInterface;
use App\Repositories\CRM\Tasks\TaskEscalationRuleRepositoryInterface;
use App\Repositories\CRM\Tasks\TaskRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-TF-001 to CRM-TF-009 — Service container bindings for Task, Activity and Follow-up module
final class CrmTaskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TaskRepositoryInterface::class,
            EloquentTaskRepository::class,
        );

        $this->app->bind(
            TaskAutoRuleRepositoryInterface::class,
            EloquentTaskAutoRuleRepository::class,
        );

        $this->app->bind(
            TaskEscalationRuleRepositoryInterface::class,
            EloquentTaskEscalationRuleRepository::class,
        );
    }

    public function boot(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskAutoRule::class, TeamTaskPolicy::class);

        Task::observe(TaskObserver::class);
        TaskAutoRule::observe(AuditObserver::class);
        TaskEscalationRule::observe(AuditObserver::class);
    }
}
