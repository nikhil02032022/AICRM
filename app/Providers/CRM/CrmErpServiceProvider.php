<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Services\CRM\Erp\ErpApiClient;
use App\Services\CRM\Erp\ErpApiClientInterface;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-LC-020 — Register ERP API client binding
final class CrmErpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interface to concrete implementation.
        // The concrete ErpApiClient is constructed per-institution at job time via
        // ErpApiClient::forInstitution($institutionId), so we bind the concrete class
        // here only for test injection / controller injection where institution is known.
        $this->app->bind(ErpApiClientInterface::class, ErpApiClient::class);
    }
}
