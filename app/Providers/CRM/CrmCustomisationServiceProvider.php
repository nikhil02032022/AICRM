<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\CustomField;
use App\Models\CRM\CustomReport;
use App\Policies\CRM\CustomFieldPolicy;
use App\Policies\CRM\CustomReportPolicy;
use App\Repositories\CRM\Analytics\CustomReportRepositoryInterface;
use App\Repositories\CRM\Analytics\EloquentCustomReportRepository;
use App\Repositories\CRM\CustomField\CustomFieldRepositoryInterface;
use App\Repositories\CRM\CustomField\EloquentCustomFieldRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-EC-005, CRM-AR-018, CRM-AR-020, CRM-SA-007, CRM-SA-011 — Group K service bindings
final class CrmCustomisationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CustomFieldRepositoryInterface::class,
            EloquentCustomFieldRepository::class,
        );

        $this->app->bind(
            CustomReportRepositoryInterface::class,
            EloquentCustomReportRepository::class,
        );
    }

    public function boot(): void
    {
        Gate::policy(CustomField::class, CustomFieldPolicy::class);
        Gate::policy(CustomReport::class, CustomReportPolicy::class);
    }
}
