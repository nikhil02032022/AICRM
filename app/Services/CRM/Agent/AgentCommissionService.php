<?php

declare(strict_types=1);

namespace App\Services\CRM\Agent;

use App\Enums\CRM\CommissionStatus;
use App\Events\CRM\AgentCommissionApprovedEvent;
use App\Jobs\CRM\ProcessAgentCommissionJob;
use App\Models\CRM\AgentCommission;
use App\Models\CRM\Lead;
use App\Repositories\CRM\Agent\AgentCommissionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

// BRD: CRM-AG-006 — Agent commission calculation, approval, and payout workflow service
final class AgentCommissionService
{
    public function __construct(
        private readonly AgentCommissionRepositoryInterface $repository
    ) {}

    /**
     * BRD: CRM-AG-006 — List all commission records for an institution (paginated)
     */
    public function list(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($institutionId, $perPage);
    }

    /**
     * BRD: CRM-AG-006 — List commissions for a specific agent
     */
    public function forAgent(int $agentUserId, int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->forAgent($agentUserId, $institutionId, $perPage);
    }

    /**
     * BRD: CRM-AG-006 — Calculate and create commission for a lead enrolment
     * Dispatches ProcessAgentCommissionJob for async amount calculation
     */
    public function create(
        int $agentUserId,
        Lead $lead,
        string $commissionType,
        float $commissionAmount,
        ?float $percentageRate = null,
        ?float $baseAmount = null
    ): AgentCommission {
        $commission = $this->repository->create([
            'uuid'              => (string) Str::uuid(),
            'institution_id'    => $lead->institution_id,
            'campus_id'         => $lead->campus_id,
            'agent_user_id'     => $agentUserId,
            'lead_id'           => $lead->id,
            'commission_type'   => $commissionType,
            'commission_amount' => $commissionAmount,
            'percentage_rate'   => $percentageRate,
            'base_amount'       => $baseAmount,
            'status'            => CommissionStatus::PENDING,
        ]);

        // BRD: CRM-AG-006 — Async commission processing (ERP fee integration for amount verification)
        ProcessAgentCommissionJob::dispatch($commission->id)->onQueue('crm-agents');

        return $commission;
    }

    /**
     * BRD: CRM-AG-006 — Approve a pending commission
     */
    public function approve(AgentCommission $commission, int $approvedByUserId, ?string $notes = null): AgentCommission
    {
        $updated = $this->repository->update($commission, [
            'status'         => CommissionStatus::APPROVED,
            'approved_by'    => $approvedByUserId,
            'approved_at'    => now(),
            'approval_notes' => $notes,
        ]);

        AgentCommissionApprovedEvent::dispatch($updated);

        return $updated;
    }

    /**
     * BRD: CRM-AG-006 — Reject a pending commission
     */
    public function reject(AgentCommission $commission, int $rejectedByUserId, string $notes): AgentCommission
    {
        return $this->repository->update($commission, [
            'status'         => CommissionStatus::REJECTED,
            'approved_by'    => $rejectedByUserId,
            'approved_at'    => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * BRD: CRM-AG-006 — Mark commission as paid with payout reference
     */
    public function markPaid(AgentCommission $commission, string $payoutReference): AgentCommission
    {
        return $this->repository->update($commission, [
            'status'           => CommissionStatus::PAID,
            'paid_at'          => now(),
            'payout_reference' => $payoutReference,
        ]);
    }

    /**
     * BRD: CRM-AG-006 — Find by UUID
     */
    public function findByUuid(string $uuid): ?AgentCommission
    {
        return $this->repository->findByUuid($uuid);
    }
}
