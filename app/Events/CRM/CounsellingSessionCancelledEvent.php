<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\CounsellingSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-EC-015 — Fired when a session is cancelled or marked no-show
final class CounsellingSessionCancelledEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CounsellingSession $session,
    ) {}
}
