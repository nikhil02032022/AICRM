<?php

declare(strict_types=1);

namespace App\Services\CRM\Compliance;

use App\Enums\CRM\Compliance\OptOutChannel;
use App\Models\CRM\Compliance\OptOutLog;
use App\Models\CRM\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

// BRD: CRM-CR-003 — Opt-out/unsubscribe honoured within 24 hours and logged
class OptOutService
{
    public function request(Lead $lead, OptOutChannel $channel): OptOutLog
    {
        return OptOutLog::create([
            'lead_id'        => $lead->id,
            'institution_id' => $lead->institution_id,
            'channel'        => $channel->value,
            'requested_at'   => now(),
        ]);
    }

    public function process(OptOutLog $log): void
    {
        DB::transaction(function () use ($log) {
            $lead = Lead::withoutGlobalScopes()->findOrFail($log->lead_id);
            $lead->update(['opt_out' => true, 'opt_out_at' => now()]);

            $log->update([
                'processed_at'     => now(),
                'processed_by_job' => true,
            ]);
        });
    }

    public function getPending(): Collection
    {
        return OptOutLog::whereNull('processed_at')->get();
    }
}
