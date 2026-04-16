<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AP-009 — Event fired when application status transitions
final class ApplicationStatusChangedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Application $application,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly ?string $reason = null,
        public readonly ?int $changedByUserId = null,
    ) {}
}
