<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\LandingPage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-005 — Fired when a landing page is created inside CRM
final class LandingPageCreatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly LandingPage $landingPage,
    ) {}
}