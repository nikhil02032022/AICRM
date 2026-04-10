<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Models\CRM\Activity;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\CounsellorAvailabilitySlot;
use App\Observers\CRM\AuditObserver;
use App\Policies\CRM\CounsellingSessionPolicy;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;
use App\Repositories\CRM\Activity\EloquentActivityRepository;
use App\Repositories\CRM\Counselling\CounsellingSessionRepositoryInterface;
use App\Repositories\CRM\Counselling\CounsellorAssignmentConfigRepositoryInterface;
use App\Repositories\CRM\Counselling\CounsellorAvailabilitySlotRepositoryInterface;
use App\Repositories\CRM\Counselling\EloquentCounsellingSessionRepository;
use App\Repositories\CRM\Counselling\EloquentCounsellorAssignmentConfigRepository;
use App\Repositories\CRM\Counselling\EloquentCounsellorAvailabilitySlotRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-EC-001 to CRM-EC-017 — Service container bindings for the Enquiry & Counselling module
final class CrmCounsellingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ActivityRepositoryInterface::class,
            EloquentActivityRepository::class,
        );

        $this->app->bind(
            CounsellorAssignmentConfigRepositoryInterface::class,
            EloquentCounsellorAssignmentConfigRepository::class,
        );

        $this->app->bind(
            CounsellingSessionRepositoryInterface::class,
            EloquentCounsellingSessionRepository::class,
        );

        $this->app->bind(
            CounsellorAvailabilitySlotRepositoryInterface::class,
            EloquentCounsellorAvailabilitySlotRepository::class,
        );
    }

    public function boot(): void
    {
        Activity::observe(AuditObserver::class);
        CounsellingSession::observe(AuditObserver::class);
        CounsellorAvailabilitySlot::observe(AuditObserver::class);

        Gate::policy(CounsellingSession::class, CounsellingSessionPolicy::class);
    }
}
