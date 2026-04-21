<?php

declare(strict_types=1);

namespace App\Listeners\CRM\Payments;

use App\Events\CRM\Payments\PaymentFailed;
use App\Models\CRM\Application;
use Illuminate\Support\Facades\Log;

// BRD: CRM-FM-005 — Notify counsellor on payment failure (logged stub for now).
class NotifyCounsellorOnPaymentFailed
{
    public function handle(PaymentFailed $event): void
    {
        $application = Application::withoutGlobalScopes()
            ->where('uuid', $event->transaction->application_uuid)
            ->first();

        Log::warning('payments.failed', [
            'application_uuid' => $application?->uuid,
            'counsellor_id'    => $application?->assigned_counsellor_id,
            'transaction_uuid' => $event->transaction->uuid,
            'reason'           => $event->reason,
        ]);
    }
}
