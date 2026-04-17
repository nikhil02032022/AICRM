<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AP-016 — Fired when ERP Student Master conversion succeeds
final class ErpConversionSucceededEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Application $application,
        public readonly string $erpStudentId,
    ) {}
}
