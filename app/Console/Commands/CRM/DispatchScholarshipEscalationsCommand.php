<?php

declare(strict_types=1);

namespace App\Console\Commands\CRM;

use App\Jobs\CRM\Scholarships\DispatchApprovalEscalationJob;
use Illuminate\Console\Command;

// BRD: CRM-FM-008
class DispatchScholarshipEscalationsCommand extends Command
{
    protected $signature = 'crm:scholarships:dispatch-escalations';

    protected $description = 'Escalate stale scholarship approvals past SLA.';

    public function handle(): int
    {
        DispatchApprovalEscalationJob::dispatch();
        $this->info('Escalation sweep dispatched.');

        return self::SUCCESS;
    }
}
