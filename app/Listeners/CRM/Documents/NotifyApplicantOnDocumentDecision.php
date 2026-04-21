<?php

declare(strict_types=1);

namespace App\Listeners\CRM\Documents;

use App\Events\CRM\Documents\DocumentRejected;
use App\Events\CRM\Documents\DocumentVerified;
use App\Models\CRM\Lead;
use App\Notifications\CRM\Documents\DocumentDecisionNotification;
use Illuminate\Support\Facades\Notification;

// BRD: CRM-DM-004 — Let the applicant know the review outcome.
class NotifyApplicantOnDocumentDecision
{
    public function handle(DocumentVerified|DocumentRejected $event): void
    {
        $doc = $event->document;
        $lead = Lead::withoutGlobalScopes()->where('uuid', $doc->lead_uuid)->first();
        if (! $lead) {
            return;
        }
        if ($lead->email) {
            Notification::route('mail', $lead->email)->notify(new DocumentDecisionNotification($doc));
        }
    }
}
