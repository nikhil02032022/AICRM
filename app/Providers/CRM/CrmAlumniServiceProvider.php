<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\Alumni\AlumniPipeline;
use App\Models\CRM\Application;
use App\Observers\CRM\Alumni\GraduationObserver;
use App\Policies\CRM\Alumni\AlumniPolicy;
use App\Services\CRM\Alumni\AlumniPipelineService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CrmAlumniServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AlumniPipelineService::class);
    }

    public function boot(): void
    {
        Application::observe(GraduationObserver::class);
        Gate::policy(AlumniPipeline::class, AlumniPolicy::class);
    }
}
