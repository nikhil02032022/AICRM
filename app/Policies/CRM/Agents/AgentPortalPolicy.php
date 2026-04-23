<?php

declare(strict_types=1);

namespace App\Policies\CRM\Agents;

use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Lead;

// BRD: CRM-AG-003 — Agent portal data scoping: agents see only their own leads
final class AgentPortalPolicy
{
    public function viewLeads(Agent $agent): bool
    {
        return $agent->isActive();
    }

    public function submitLead(Agent $agent): bool
    {
        return $agent->isActive();
    }

    public function viewLead(Agent $agent, Lead $lead): bool
    {
        return $agent->isActive()
            && $lead->agent_id === $agent->id;
    }
}
