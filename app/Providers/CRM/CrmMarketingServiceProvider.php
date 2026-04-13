<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\LandingPage;
use App\Models\CRM\AutomationWorkflow;
use App\Policies\CRM\AutomationWorkflowPolicy;
use App\Policies\CRM\LandingPagePolicy;
use App\Repositories\CRM\Marketing\AutomationWorkflowRepositoryInterface;
use App\Repositories\CRM\Marketing\CampaignSpendRepositoryInterface;
use App\Repositories\CRM\Marketing\ChatLeadRepositoryInterface;
use App\Repositories\CRM\Marketing\EloquentAutomationWorkflowRepository;
use App\Repositories\CRM\Marketing\EloquentCampaignSpendRepository;
use App\Repositories\CRM\Marketing\EloquentChatLeadRepository;
use App\Repositories\CRM\Marketing\EloquentLeadAttributionRepository;
use App\Repositories\CRM\Marketing\EloquentLandingPageRepository;
use App\Repositories\CRM\Marketing\LeadAttributionRepositoryInterface;
use App\Repositories\CRM\Marketing\LandingPageRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-LC-005 — Service container bindings for the CRM marketing landing page module
final class CrmMarketingServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        LandingPageRepositoryInterface::class => EloquentLandingPageRepository::class,
        AutomationWorkflowRepositoryInterface::class => EloquentAutomationWorkflowRepository::class,
        ChatLeadRepositoryInterface::class => EloquentChatLeadRepository::class,
        LeadAttributionRepositoryInterface::class => EloquentLeadAttributionRepository::class,
        CampaignSpendRepositoryInterface::class => EloquentCampaignSpendRepository::class,
    ];

    public function register(): void
    {
        // Bindings handled via $bindings.
    }

    public function boot(): void
    {
        Gate::policy(LandingPage::class, LandingPagePolicy::class);
        Gate::policy(AutomationWorkflow::class, AutomationWorkflowPolicy::class);
    }
}