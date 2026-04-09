<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\WebForm;
use App\Policies\CRM\WebFormPolicy;
use App\Repositories\CRM\WebForm\EloquentWebFormRepository;
use App\Repositories\CRM\WebForm\WebFormRepositoryInterface;
use Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-LC-001 — Service container bindings for the CRM WebForm module
final class CrmWebFormServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        WebFormRepositoryInterface::class => EloquentWebFormRepository::class,
    ];

    public function register(): void
    {
        // Bindings handled via $bindings array above
    }

    public function boot(): void
    {
        Gate::policy(WebForm::class, WebFormPolicy::class);
    }
}
