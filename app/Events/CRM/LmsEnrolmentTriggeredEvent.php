<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\LmsEnrolmentLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-EI-010 — Fired when an LMS enrolment is triggered for an admitted student
final class LmsEnrolmentTriggeredEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly LmsEnrolmentLog $enrolmentLog
    ) {}
}
