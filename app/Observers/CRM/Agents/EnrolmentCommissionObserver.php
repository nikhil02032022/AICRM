<?php

declare(strict_types=1);

namespace App\Observers\CRM\Agents;

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Application;
use App\Services\CRM\Agents\CommissionAccrualService;

// BRD: CRM-AG-005 — Fire commission accrual when application transitions to ENROLLED
final class EnrolmentCommissionObserver
{
    public function __construct(private readonly CommissionAccrualService $accrualService) {}

    public function updated(Application $application): void
    {
        $original = $application->getOriginal('status');
        $current  = $application->status;

        // Only act on transitions TO enrolled — not on other updates
        // getOriginal() returns the cast value (enum) when the model has a cast, not the raw string
        $originalStatus = $original instanceof ApplicationStatus
            ? $original
            : ApplicationStatus::from($original);

        if (
            $original !== null &&
            $originalStatus !== ApplicationStatus::ENROLLED &&
            $current === ApplicationStatus::ENROLLED
        ) {
            $this->accrualService->accrue($application);
        }
    }
}
