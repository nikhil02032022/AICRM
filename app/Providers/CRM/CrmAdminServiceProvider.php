<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\Admin\AcademicYear;
use App\Models\CRM\Admin\SystemConfig;
use App\Models\CRM\AuditLog;
use App\Models\CRM\Campus;
use App\Models\CRM\Institution;
use App\Policies\CRM\Admin\AuditLogPolicy;
use App\Policies\CRM\Admin\CampusPolicy;
use App\Policies\CRM\Admin\InstitutionPolicy;
use App\Policies\CRM\Admin\SystemConfigPolicy;
use App\Services\CRM\Admin\AcademicYearService;
use App\Services\CRM\Admin\DataExportService;
use App\Services\CRM\Admin\DataImportService;
use App\Services\CRM\Admin\NotificationTemplateService;
use App\Services\CRM\Admin\SystemConfigService;
use App\Services\CRM\Admin\TenancyService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CrmAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenancyService::class);
        $this->app->singleton(AcademicYearService::class);
        $this->app->singleton(SystemConfigService::class);
        $this->app->singleton(NotificationTemplateService::class);
        $this->app->singleton(DataImportService::class);
        $this->app->singleton(DataExportService::class);
    }

    public function boot(): void
    {
        Gate::policy(Institution::class, InstitutionPolicy::class);
        Gate::policy(Campus::class, CampusPolicy::class);
        Gate::policy(AcademicYear::class, \App\Policies\CRM\Admin\AcademicYearPolicy::class);
        Gate::policy(SystemConfig::class, SystemConfigPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
