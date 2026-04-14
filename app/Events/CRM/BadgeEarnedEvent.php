<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\CounsellorBadge;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BRD: CRM-EC-010 — Event fired when a counsellor earns a badge
 */
class BadgeEarnedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CounsellorBadge $counsellorBadge
    ) {}
}
