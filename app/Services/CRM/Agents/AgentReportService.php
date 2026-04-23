<?php

declare(strict_types=1);

namespace App\Services\CRM\Agents;

use App\Enums\CRM\Agents\CommissionAccrualStatus;
use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentCommissionAccrual;
use App\Models\CRM\Lead;
use Illuminate\Support\Carbon;

// BRD: CRM-AG-007 — Agent performance report: leads, conversions, revenue, commissions
final class AgentReportService
{
    /**
     * Aggregate performance metrics for a single agent.
     *
     * @param array{from?: string, to?: string} $filters
     * @return array{
     *   total_leads: int,
     *   total_conversions: int,
     *   conversion_rate: float,
     *   total_revenue: float,
     *   total_accrued_commission: float,
     *   pending_commission: float,
     *   approved_commission: float,
     *   paid_commission: float,
     * }
     */
    public function forAgent(Agent $agent, array $filters = []): array
    {
        $from = isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
        $to   = isset($filters['to'])   ? Carbon::parse($filters['to'])->endOfDay()     : null;

        $leadsQuery = Lead::withoutGlobalScopes()
            ->where('agent_id', $agent->id);

        if ($from) {
            $leadsQuery->where('created_at', '>=', $from);
        }
        if ($to) {
            $leadsQuery->where('created_at', '<=', $to);
        }

        $totalLeads = $leadsQuery->count();

        // Conversions = leads with at least one ENROLLED application
        $totalConversions = $leadsQuery
            ->clone()
            ->whereHas('applications', fn ($q) => $q->where('status', ApplicationStatus::ENROLLED->value))
            ->count();

        $conversionRate = $totalLeads > 0
            ? round($totalConversions / $totalLeads * 100, 1)
            : 0.0;

        // Revenue = sum of confirmed payments on agent's leads
        $totalRevenue = (float) Lead::withoutGlobalScopes()
            ->where('agent_id', $agent->id)
            ->join('applications', 'applications.lead_uuid', '=', 'leads.uuid')
            ->join('payment_transactions', 'payment_transactions.application_uuid', '=', 'applications.uuid')
            ->where('payment_transactions.status', PaymentStatus::SUCCESS->value)
            ->sum('payment_transactions.amount');

        $accrualQuery = AgentCommissionAccrual::withoutGlobalScopes()
            ->where('agent_id', $agent->id);

        $totalAccrued  = (float) (clone $accrualQuery)->sum('commission_amount');
        $pendingTotal  = (float) (clone $accrualQuery)->where('status', CommissionAccrualStatus::Pending->value)->sum('commission_amount');
        $approvedTotal = (float) (clone $accrualQuery)->where('status', CommissionAccrualStatus::Approved->value)->sum('commission_amount');
        $paidTotal     = (float) (clone $accrualQuery)->where('status', CommissionAccrualStatus::Paid->value)->sum('commission_amount');

        return [
            'total_leads'             => $totalLeads,
            'total_conversions'       => $totalConversions,
            'conversion_rate'         => $conversionRate,
            'total_revenue'           => $totalRevenue,
            'total_accrued_commission' => $totalAccrued,
            'pending_commission'      => $pendingTotal,
            'approved_commission'     => $approvedTotal,
            'paid_commission'         => $paidTotal,
        ];
    }

    /**
     * Aggregate performance for all agents in an institution (for the report index).
     *
     * @param array<string, mixed> $filters
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function forInstitution(int $institutionId, array $filters = []): \Illuminate\Support\Collection
    {
        return Agent::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->with('referralCode')
            ->get()
            ->map(fn (Agent $agent) => array_merge(
                ['agent' => $agent],
                $this->forAgent($agent, $filters),
            ));
    }
}
