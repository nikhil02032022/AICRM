<?php

declare(strict_types=1);

namespace App\Services\CRM\Compliance;

use App\Enums\CRM\Compliance\PiiErasureStatus;
use App\Models\CRM\Compliance\PiiErasureRequest;
use App\Models\CRM\Lead;
use Illuminate\Support\Facades\DB;

// BRD: CRM-CR-005 — Right-to-erasure: PII anonymised within 30 days of verified request
class PiiErasureService
{
    public function schedule(Lead $lead, int $institutionId): PiiErasureRequest
    {
        return PiiErasureRequest::create([
            'lead_id'              => $lead->id,
            'institution_id'       => $institutionId,
            'requested_at'         => now(),
            'scheduled_erasure_at' => now()->addDays(30),
            'status'               => PiiErasureStatus::Scheduled->value,
        ]);
    }

    public function erase(PiiErasureRequest $request): void
    {
        DB::transaction(function () use ($request) {
            $lead = Lead::withoutGlobalScopes()->findOrFail($request->lead_id);

            // Call existing anonymisePII method on Lead model (DPDP compliance)
            $lead->anonymisePII();

            $request->update([
                'erased_at'     => now(),
                'erased_by_job' => true,
                'status'        => PiiErasureStatus::Erased->value,
            ]);
        });
    }

    public function getDue(): \Illuminate\Database\Eloquent\Collection
    {
        return PiiErasureRequest::withoutGlobalScopes()
            ->where('status', PiiErasureStatus::Scheduled->value)
            ->where('scheduled_erasure_at', '<=', now())
            ->get();
    }
}
