<?php

declare(strict_types=1);

namespace App\Providers\CRM;

use App\Services\CRM\Scholarships\ScholarshipAwardService;
use App\Services\CRM\Scholarships\ScholarshipCategoryService;
use App\Services\CRM\Scholarships\ScholarshipEligibilityEvaluator;
use App\Services\CRM\Scholarships\ScholarshipImpactReporter;
use Illuminate\Support\ServiceProvider;

// BRD: CRM-FM-006 to CRM-FM-008 — container bindings for scholarship module.
final class CrmScholarshipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ScholarshipCategoryService::class);
        $this->app->singleton(ScholarshipAwardService::class);
        $this->app->singleton(ScholarshipEligibilityEvaluator::class);
        $this->app->singleton(ScholarshipImpactReporter::class);
    }

    public function boot(): void
    {
        // Policies / gates are registered centrally; nothing to do here.
    }
}
