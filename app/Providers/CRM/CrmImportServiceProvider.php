<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Http\Middleware\CRM\VerifyWebhookSignature;
use App\Models\CRM\IntegrationCredential;
use App\Policies\CRM\IntegrationCredentialPolicy;
use App\Repositories\CRM\Import\EloquentIntegrationCredentialRepository;
use App\Repositories\CRM\Import\EloquentLeadImportBatchRepository;
use App\Repositories\CRM\Import\IntegrationCredentialRepositoryInterface;
use App\Repositories\CRM\Import\LeadImportBatchRepositoryInterface;
use Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-LC-003, CRM-LC-004, CRM-LC-008, CRM-LC-012, CRM-SA-010
// Service container bindings for the Group C digital channel imports module
final class CrmImportServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        IntegrationCredentialRepositoryInterface::class => EloquentIntegrationCredentialRepository::class,
        LeadImportBatchRepositoryInterface::class        => EloquentLeadImportBatchRepository::class,
    ];

    public function register(): void
    {
        // Bindings handled via $bindings array above
    }

    public function boot(): void
    {
        // Register integration credential policy
        Gate::policy(IntegrationCredential::class, IntegrationCredentialPolicy::class);

        // Register webhook signature middleware alias
        $this->app['router']->aliasMiddleware(
            'crm.webhook',
            VerifyWebhookSignature::class,
        );
    }
}
