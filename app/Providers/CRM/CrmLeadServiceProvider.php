<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\Lead;
use App\Observers\CRM\AuditObserver;
use App\Policies\CRM\LeadPolicy;
use App\Repositories\CRM\Lead\EloquentLeadRepository;
use App\Repositories\CRM\Lead\LeadRepositoryInterface;
use Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-LC-011 — Service container bindings for the CRM Lead module
final class CrmLeadServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        LeadRepositoryInterface::class => EloquentLeadRepository::class,
    ];

    public function register(): void
    {
        // Bindings handled via $bindings array above
    }

    public function boot(): void
    {
        // Register Lead policy
        Gate::policy(Lead::class, LeadPolicy::class);

        // Register observer (also registered via #[ObservedBy] attribute on the model)
        Lead::observe(AuditObserver::class);
    }
}
