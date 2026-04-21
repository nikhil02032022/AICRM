<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Services\CRM\Documents\ApplicationDocumentService;
use App\Services\CRM\Documents\BulkDownloadService;
use App\Services\CRM\Documents\DocumentChecklistService;
use App\Services\CRM\Documents\DocumentCompletenessCalculator;
use App\Services\CRM\Documents\DocumentEncryptionManager;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-DM-001 to CRM-DM-010 — container bindings for document module.
final class CrmDocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentEncryptionManager::class, function ($app) {
            return new DocumentEncryptionManager(
                (string) config('crm_documents.storage.disk', 'encrypted_documents')
            );
        });
        $this->app->singleton(DocumentChecklistService::class);
        $this->app->singleton(DocumentCompletenessCalculator::class);
        $this->app->singleton(ApplicationDocumentService::class);
        $this->app->singleton(BulkDownloadService::class);
    }

    public function boot(): void
    {
    }
}
