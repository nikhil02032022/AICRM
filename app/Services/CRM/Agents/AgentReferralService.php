<?php

declare(strict_types=1);

namespace App\Services\CRM\Agents;

use App\Enums\CRM\LeadSource;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentReferralCode;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Illuminate\Http\Request;

// BRD: CRM-AG-002 — Generate unique referral codes and attribute leads via ?ref= query param
final class AgentReferralService
{
    public function generateCode(Agent $agent): AgentReferralCode
    {
        $institution = Institution::withoutGlobalScopes()->find($agent->institution_id);
        $prefix      = $this->institutionShortCode($institution?->name ?? 'INST');

        do {
            $hex  = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $code = "{$prefix}-AG-{$hex}";
            $slug = strtolower("{$prefix}-ag-{$hex}");
        } while (AgentReferralCode::withoutGlobalScopes()->where('code', $code)->exists());

        return AgentReferralCode::create([
            'agent_id'       => $agent->id,
            'institution_id' => $agent->institution_id,
            'code'           => $code,
            'url_slug'       => $slug,
        ]);
    }

    /**
     * Resolve an Agent from the ?ref=CODE query param.
     * Returns null when the code is missing or unknown.
     */
    public function resolveFromRequest(Request $request): ?Agent
    {
        $code = $request->query('ref');

        if (! $code) {
            return null;
        }

        $referral = AgentReferralCode::withoutGlobalScopes()
            ->where('code', $code)
            ->with('agent')
            ->first();

        if ($referral === null || $referral->agent === null) {
            return null;
        }

        return $referral->agent;
    }

    /**
     * Attribute a lead to an agent: set source=AGENT and agent_id, increment referral count.
     */
    public function attributeLead(Lead $lead, Agent $agent): void
    {
        $lead->update([
            'agent_id' => $agent->id,
            'source'   => LeadSource::AGENT,
        ]);

        $this->incrementLeadCount($agent);
    }

    public function incrementLeadCount(Agent $agent): void
    {
        AgentReferralCode::withoutGlobalScopes()
            ->where('agent_id', $agent->id)
            ->increment('total_leads');
    }

    public function incrementConversionCount(Agent $agent): void
    {
        AgentReferralCode::withoutGlobalScopes()
            ->where('agent_id', $agent->id)
            ->increment('total_conversions');
    }

    private function institutionShortCode(string $name): string
    {
        // Use first 6 uppercase alphanum chars of institution name
        $slug = preg_replace('/[^A-Z0-9]/', '', strtoupper($name));
        return substr($slug ?: 'INST', 0, 6);
    }
}
