<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\CounsellingSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-EC-015 — Fired when a session is marked completed
final class CounsellingSessionCompletedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CounsellingSession $session,
    ) {}
}
