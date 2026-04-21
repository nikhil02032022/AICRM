<?php

declare(strict_types=1);

namespace App\Listeners\CRM\Documents;

use App\Events\CRM\Documents\DocumentRejected;
use App\Events\CRM\Documents\DocumentUploaded;
use App\Events\CRM\Documents\DocumentVerified;
use App\Models\CRM\Application;
use App\Services\CRM\Documents\DocumentCompletenessCalculator;

// BRD: CRM-DM-010 — Invalidate cached completeness score on any document state change.
class UpdateCompletenessOnDocumentChange
{
    public function __construct(private DocumentCompletenessCalculator $calculator)
    {
    }

    public function handle(DocumentUploaded|DocumentVerified|DocumentRejected $event): void
    {
        $application = Application::withoutGlobalScopes()
            ->where('uuid', $event->document->application_uuid)
            ->first();

        if ($application) {
            $this->calculator->invalidate($application);
        }
    }
}
