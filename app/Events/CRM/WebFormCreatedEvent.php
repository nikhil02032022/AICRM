<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\WebForm;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-001 — Fired when a new WebForm is created by institution staff
final class WebFormCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WebForm $form,
    ) {}
}
