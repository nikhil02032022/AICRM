<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\ApplicationConversionLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AP-016 — Fired when ERP Student Master conversion fails (after API error or null response)
final class ErpConversionFailedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationConversionLog $log,
        public readonly string $errorMessage,
    ) {}
}
