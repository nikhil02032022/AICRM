<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Models\CRM\Lead;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-TC-009 — Do-Not-Call (DNC) list management built into the telecalling module
final class DncService
{
    /**
     * BRD: CRM-TC-009 — Add a lead to the DNC list.
     * Idempotent: safe to call multiple times — only the first call sets dnc_at.
     * DPDP: opt-out must take effect immediately; no further calls may be dispatched.
     */
    public function addToDnc(Lead $lead, string $reason): void
    {
        // Idempotent — only stamp dnc_at when not already set
        if ($lead->dnc_at !== null) {
            return;
        }

        $lead->update([
            // BRD: CRM-CC-006 + CRM-TC-009 — Universal DNC stamp; blocks calls, SMS, email
            'dnc_at'     => now(),
            'dnc_reason' => $reason,
            // Also propagate general opt-out so all communication channels are blocked
            'opt_out'    => true,
            'opt_out_at' => $lead->opt_out_at ?? now(),
        ]);
    }

    /**
     * BRD: CRM-TC-009 — Remove a lead from the DNC list (manual reinstatement by admin).
     * Requires permission: crm.dnc.manage
     * DPDP: removal is logged via AuditObserver; lead may reinstate communication channels only
     * after explicit re-consent.
     */
    public function removeFromDnc(Lead $lead): void
    {
        if ($lead->dnc_at === null) {
            return;
        }

        $lead->update([
            'dnc_at'     => null,
            'dnc_reason' => null,
        ]);
        // Note: opt_out is NOT cleared here — the lead must re-consent separately.
    }

    /**
     * BRD: CRM-TC-009 — Paginated DNC list for the institution.
     * Scoped by InstitutionScope global scope automatically.
     *
     * @return LengthAwarePaginator<Lead>
     */
    public function paginateDncLeads(int $institutionId, string $search = '', int $perPage = 25): LengthAwarePaginator
    {
        return Lead::query()
            ->whereNotNull('dnc_at')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('dnc_reason', 'like', "%{$search}%");
                });
            })
            ->with(['assignedCounsellor:id,name'])
            ->orderByDesc('dnc_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
