<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\Compliance\DataAccessRequest;
use App\Models\CRM\Compliance\OptOutLog;
use App\Models\CRM\Compliance\PiiErasureRequest;
use App\Models\CRM\Compliance\SecurityIncident;
use App\Policies\CRM\Compliance\CompliancePolicy;
use App\Services\CRM\Compliance\BreachNotificationService;
use App\Services\CRM\Compliance\ConsentService;
use App\Services\CRM\Compliance\DataAccessService;
use App\Services\CRM\Compliance\DataResidencyService;
use App\Services\CRM\Compliance\DltTemplateValidatorService;
use App\Services\CRM\Compliance\OptOutService;
use App\Services\CRM\Compliance\PiiErasureService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CrmComplianceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConsentService::class);
        $this->app->singleton(OptOutService::class);
        $this->app->singleton(DataAccessService::class);
        $this->app->singleton(PiiErasureService::class);
        $this->app->singleton(DataResidencyService::class);
        $this->app->singleton(DltTemplateValidatorService::class);
        $this->app->singleton(BreachNotificationService::class);
    }

    public function boot(): void
    {
        Gate::policy(SecurityIncident::class, CompliancePolicy::class);
        Gate::policy(OptOutLog::class, CompliancePolicy::class);
        Gate::policy(DataAccessRequest::class, CompliancePolicy::class);
        Gate::policy(PiiErasureRequest::class, CompliancePolicy::class);
    }
}
