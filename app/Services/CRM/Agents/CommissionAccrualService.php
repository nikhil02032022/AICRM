<?php

declare(strict_types=1);

namespace App\Services\CRM\Agents;

use App\Enums\CRM\Agents\CommissionStructureType;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentCommissionAccrual;
use App\Models\CRM\Agents\AgentCommissionStructure;
use App\Models\CRM\Application;
use App\Models\CRM\Lead;
use Illuminate\Support\Carbon;

// BRD: CRM-AG-005 — Auto-calculate and record commission accrual on enrolment confirmation
final class CommissionAccrualService
{
    /**
     * Calculate and persist a commission accrual for the enrolled application.
     * Returns null when no agent is attributed or no active commission structure exists.
     */
    public function accrue(Application $application): ?AgentCommissionAccrual
    {
        /** @var Lead|null $lead */
        $lead = $application->lead()->withoutGlobalScopes()->first();

        if ($lead === null || $lead->agent_id === null) {
            return null;
        }

        /** @var Agent|null $agent */
        $agent = Agent::withoutGlobalScopes()->find($lead->agent_id);

        if ($agent === null) {
            return null;
        }

        /** @var AgentCommissionStructure|null $structure */
        $structure = AgentCommissionStructure::withoutGlobalScopes()
            ->where('agent_id', $agent->id)
            ->where('programme_id', $application->programme_id)
            ->activeAt(Carbon::today())
            ->latest('effective_from')
            ->first();

        if ($structure === null) {
            return null;
        }

        [$commissionAmount, $basisAmount] = $this->calculate($application, $structure);

        $accrual = AgentCommissionAccrual::create([
            'institution_id'       => $application->institution_id,
            'agent_id'             => $agent->id,
            'application_id'       => $application->id,
            'lead_id'              => $lead->id,
            'programme_id'         => $application->programme_id,
            'structure_id'         => $structure?->id,
            'accrual_basis_amount' => $basisAmount,
            'commission_amount'    => $commissionAmount,
            'accrued_at'           => Carbon::now(),
        ]);

        // Increment conversion count on the referral code
        app(AgentReferralService::class)->incrementConversionCount($agent);

        return $accrual;
    }

    /**
     * @return array{float, float} [commissionAmount, basisAmount]
     */
    private function calculate(Application $application, ?AgentCommissionStructure $structure): array
    {
        if ($structure === null) {
            return [0.0, 0.0];
        }

        return match ($structure->structure_type) {
            CommissionStructureType::PerEnrolment, CommissionStructureType::PerApplication => [
                (float) $structure->amount,
                0.0,
            ],
            CommissionStructureType::PercentageFee => $this->calculatePercentage($application, $structure),
        };
    }

    /**
     * @return array{float, float}
     */
    private function calculatePercentage(Application $application, AgentCommissionStructure $structure): array
    {
        $confirmedAmount = $application
            ->transactions()
            ->where('status', PaymentStatus::SUCCESS->value)
            ->sum('amount');

        $basisAmount      = (float) $confirmedAmount;
        $commissionAmount = round($basisAmount * ((float) $structure->percentage / 100), 2);

        return [$commissionAmount, $basisAmount];
    }
}
