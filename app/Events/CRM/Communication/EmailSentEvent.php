<?php

declare(strict_types=1);

namespace App\Events\CRM\Communication;

use App\Models\CRM\CommunicationLog;
use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-002 — Email sent event
final class EmailSentEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly CommunicationLog $log,
    ) {}
}
