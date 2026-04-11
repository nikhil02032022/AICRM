<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\DTOs\CRM\ErpStudentDTO;
use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-020 — Fired when a CRM lead is matched to an existing ERP student/alumni record
final class ErpStudentMatchedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly ErpStudentDTO $erpStudent,
    ) {}
}
