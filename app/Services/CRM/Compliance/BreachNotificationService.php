<?php

declare(strict_types=1);

namespace App\Services\CRM\Compliance;

use App\Jobs\CRM\Compliance\BreachNotificationJob;
use App\Models\CRM\Compliance\SecurityIncident;
use App\Models\User;

// BRD: CRM-CR-010 — Breach notification workflow: alert institution admin within 72h
class BreachNotificationService
{
    public function create(array $incidentData, User $reportedBy): SecurityIncident
    {
        return SecurityIncident::create(array_merge($incidentData, [
            'reported_by' => $reportedBy->id,
            'detected_at' => $incidentData['detected_at'] ?? now(),
        ]));
    }

    public function notify(SecurityIncident $incident): void
    {
        BreachNotificationJob::dispatch($incident);

        $incident->update(['notified_at' => now()]);
    }
}
