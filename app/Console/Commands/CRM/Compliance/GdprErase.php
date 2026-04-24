<?php

declare(strict_types=1);

namespace App\Console\Commands\CRM\Compliance;

use App\Enums\CRM\Compliance\PiiErasureStatus;
use App\Models\CRM\Compliance\PiiErasureRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Compliance\PiiErasureService;
use Illuminate\Console\Command;

// BRD: CRM-CR-005 — Manual trigger for individual PII erasure
class GdprErase extends Command
{
    protected $signature = 'crm:gdpr:erase {lead_id : The numeric ID of the lead to erase}';

    protected $description = 'Manually anonymise PII for a lead (DPDP Act right-to-erasure)';

    public function handle(PiiErasureService $service): int
    {
        $leadId = (int) $this->argument('lead_id');

        $lead = Lead::withoutGlobalScopes()->find($leadId);

        if (! $lead) {
            $this->error("Lead #{$leadId} not found.");

            return self::FAILURE;
        }

        if ($lead->isAnonymised()) {
            $this->warn("Lead #{$leadId} PII has already been anonymised.");

            return self::SUCCESS;
        }

        // Create or find an erasure request
        $request = PiiErasureRequest::withoutGlobalScopes()->firstOrCreate(
            ['lead_id' => $leadId],
            [
                'institution_id'       => $lead->institution_id,
                'requested_at'         => now(),
                'scheduled_erasure_at' => now(),
                'status'               => PiiErasureStatus::Scheduled->value,
            ]
        );

        $this->info("Erasing PII for lead #{$leadId} [{$lead->first_name} {$lead->last_name}]...");

        $service->erase($request);

        $this->info('PII anonymised successfully.');
        $this->line('  mobile → [ERASED]');
        $this->line('  email  → [ERASED]');
        $this->line('  Erased at: '.now()->format('d M Y H:i:s'));

        return self::SUCCESS;
    }
}
