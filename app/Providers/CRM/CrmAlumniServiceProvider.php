<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\Alumni\AlumniNpsSnapshot;
use App\Models\CRM\Alumni\AlumniPipeline;
use App\Models\CRM\Alumni\AlumniReferralCampaign;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Observers\CRM\Alumni\ApplicationConversionReferralObserver;
use App\Observers\CRM\Alumni\GraduationObserver;
use App\Policies\CRM\Alumni\AlumniNpsPolicy;
use App\Policies\CRM\Alumni\AlumniPolicy;
use App\Policies\CRM\Alumni\AlumniReferralCampaignPolicy;
use App\Services\CRM\Alumni\AlumniNpsService;
use App\Services\CRM\Alumni\AlumniPipelineService;
use App\Services\CRM\Alumni\AlumniReferralService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CrmAlumniServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AlumniPipelineService::class);
        $this->app->singleton(AlumniReferralService::class);
        $this->app->singleton(AlumniNpsService::class);
    }

    public function boot(): void
    {
        Application::observe(GraduationObserver::class);
        ApplicationConversionLog::observe(ApplicationConversionReferralObserver::class);

        Gate::policy(AlumniPipeline::class, AlumniPolicy::class);
        Gate::policy(AlumniReferralCampaign::class, AlumniReferralCampaignPolicy::class);
        Gate::policy(AlumniNpsSnapshot::class, AlumniNpsPolicy::class);
    }
}
