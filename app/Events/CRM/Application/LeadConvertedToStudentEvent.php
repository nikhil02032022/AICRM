<?php

declare(strict_types=1);

namespace App\Events\CRM\Application;

use App\Models\CRM\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AP-016, CRM-AP-017 — Event fired when applicant is converted to ERP Student
final class LeadConvertedToStudentEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Application $application,
        public readonly string $erpStudentId,
        public readonly ?int $convertedByUserId = null,
    ) {}
}
