<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Enums\CRM\LeadTemperature;
use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LQ-006 — Fired when a lead's temperature classification changes; triggers automated workflow
final class LeadTemperatureChangedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly LeadTemperature $oldTemperature,
        public readonly LeadTemperature $newTemperature,
    ) {}
}
