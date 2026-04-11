<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\LeadCreatedEvent;
use App\Services\CRM\Marketing\AttributionService;

// BRD: CRM-LC-016 — Create first attribution touchpoint whenever a lead is created.
final class CaptureLeadAttributionOnCreate
{
    public function __construct(
        private readonly AttributionService $attributionService,
    ) {}

    public function handle(LeadCreatedEvent $event): void
    {
        $this->attributionService->recordInitialTouchpoint($event->lead);
    }
}
