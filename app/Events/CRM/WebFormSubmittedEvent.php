<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\Lead;
use App\Models\CRM\WebForm;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-001 — Fired after a public web form submission successfully creates a Lead
// BRD: CRM-CR-001 — Consent has already been captured in the Lead record at this point
final class WebFormSubmittedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WebForm $form,
        public readonly Lead    $lead,
    ) {}
}
