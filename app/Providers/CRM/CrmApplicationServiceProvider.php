<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\Application;
use App\Models\CRM\ApplicationFormTemplate;
use App\Models\CRM\ApplicationFormDraft;
use App\Models\CRM\OfferLetter;
use App\Models\CRM\OfferLetterTemplate;
use App\Models\CRM\ApplicationConversionLog;
use App\Policies\CRM\ApplicationPolicy;
use App\Policies\CRM\ErpConversionPolicy;
use App\Policies\CRM\ApplicationFormDraftPolicy;
use App\Policies\CRM\ApplicationFormTemplatePolicy;
use App\Policies\CRM\OfferLetterPolicy;
use App\Repositories\CRM\Application\ApplicationRepositoryInterface;
use App\Repositories\CRM\Application\EloquentApplicationRepository;
use App\Repositories\CRM\Application\ApplicationFormDraftRepositoryInterface;
use App\Repositories\CRM\Application\EloquentApplicationFormDraftRepository;
use App\Repositories\CRM\Application\ApplicationFormTemplateRepositoryInterface;
use App\Repositories\CRM\Application\EloquentApplicationFormTemplateRepository;
use App\Repositories\CRM\Application\OfferLetterRepositoryInterface;
use App\Repositories\CRM\Application\EloquentOfferLetterRepository;
use App\Repositories\CRM\Application\OfferLetterTemplateRepositoryInterface;
use App\Repositories\CRM\Application\EloquentOfferLetterTemplateRepository;
use App\Repositories\CRM\Application\ApplicationConversionLogRepositoryInterface;
use App\Repositories\CRM\Application\EloquentApplicationConversionLogRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-AP-001, CRM-AP-008, CRM-AP-012, CRM-AP-016 — Service container bindings for application module
final class CrmApplicationServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        ApplicationFormTemplateRepositoryInterface::class => EloquentApplicationFormTemplateRepository::class,
        ApplicationFormDraftRepositoryInterface::class => EloquentApplicationFormDraftRepository::class,
        ApplicationRepositoryInterface::class => EloquentApplicationRepository::class,
        OfferLetterRepositoryInterface::class => EloquentOfferLetterRepository::class,
        OfferLetterTemplateRepositoryInterface::class => EloquentOfferLetterTemplateRepository::class,
        ApplicationConversionLogRepositoryInterface::class => EloquentApplicationConversionLogRepository::class,
        \App\Repositories\CRM\Application\ApplicationConversionReportRepositoryInterface::class => \App\Repositories\CRM\Application\EloquentApplicationConversionReportRepository::class,
    ];

    public function register(): void
    {
        // Bindings handled via $bindings array above
    }

    public function boot(): void
    {
        Gate::policy(ApplicationFormTemplate::class, ApplicationFormTemplatePolicy::class);
        Gate::policy(ApplicationFormDraft::class, ApplicationFormDraftPolicy::class);
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(OfferLetter::class, OfferLetterPolicy::class);
        Gate::policy(ApplicationConversionLog::class, ErpConversionPolicy::class);
    }
}
