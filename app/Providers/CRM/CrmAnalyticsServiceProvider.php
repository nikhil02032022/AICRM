<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\Analytics\DashboardMetricSnapshot;
use App\Policies\CRM\Analytics\DashboardPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-AR-001 to CRM-AR-021 — Analytics module service bindings and policy registrations
final class CrmAnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(DashboardMetricSnapshot::class, DashboardPolicy::class);
    }
}
