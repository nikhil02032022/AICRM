<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CRM\TenantManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // BRD: Multi-tenancy — TenantManager resolves institution_id for every request
        $this->app->singleton(TenantManager::class, fn () => new TenantManager);
    }

    public function boot(): void
    {
        //
    }
}
