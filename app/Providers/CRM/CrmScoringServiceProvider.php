<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\InstitutionScoringConfig;
use App\Models\CRM\Lead;
use App\Policies\CRM\ScoringConfigPolicy;
use App\Repositories\CRM\Scoring\EloquentScoringConfigRepository;
use App\Repositories\CRM\Scoring\ScoringConfigRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-LQ-001 to CRM-LQ-008 — Service bindings and policy registration for the scoring module
final class CrmScoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ScoringConfigRepositoryInterface::class,
            EloquentScoringConfigRepository::class,
        );
    }

    public function boot(): void
    {
        // BRD: A01 — Register scoring RBAC gates
        Gate::policy(InstitutionScoringConfig::class, ScoringConfigPolicy::class);

        // Override gate for Lead (scoring override action is on Lead model)
        // Delegates to ScoringConfigPolicy::override which checks role + assignment
        Gate::define('override', [ScoringConfigPolicy::class, 'override']);
    }
}
