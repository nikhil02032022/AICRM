<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Application;
use App\Observers\CRM\Agents\EnrolmentCommissionObserver;
use App\Policies\CRM\Agents\AgentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-AG-001 to CRM-AG-007 — Service container bindings for Agent and Channel Partner module
final class CrmAgentServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Agent::class, AgentPolicy::class);

        Application::observe(EnrolmentCommissionObserver::class);
    }
}
