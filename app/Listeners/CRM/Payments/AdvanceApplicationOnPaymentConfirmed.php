<?php

declare(strict_types=1);

namespace App\Listeners\CRM\Payments;

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\Payments\FeeType;
use App\Events\CRM\Payments\PaymentConfirmed;
use App\Models\CRM\Application;

// BRD: CRM-FM-005 — Auto-update application status on confirmed payment.
class AdvanceApplicationOnPaymentConfirmed
{
    public function handle(PaymentConfirmed $event): void
    {
        $txn = $event->transaction;

        $application = Application::withoutGlobalScopes()
            ->where('uuid', $txn->application_uuid)
            ->first();

        if ($application === null) {
            return;
        }

        // Seat-booking payment promotes an OFFER_ISSUED application to OFFER_ACCEPTED.
        if (
            $txn->fee_type === FeeType::SEAT_BOOKING
            && $application->status === ApplicationStatus::OFFER_ISSUED
            && $application->canTransitionTo(ApplicationStatus::OFFER_ACCEPTED)
        ) {
            $application->status = ApplicationStatus::OFFER_ACCEPTED;
            $application->stage_entered_at = now();
            $application->save();
        }
    }
}
