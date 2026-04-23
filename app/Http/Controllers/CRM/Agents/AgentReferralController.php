<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Agents;

use App\Http\Controllers\Controller;
use App\Models\CRM\Agents\Agent;
use Illuminate\View\View;

// BRD: CRM-AG-002 — Display agent referral code card with shareable link
final class AgentReferralController extends Controller
{
    public function show(Agent $agent): View
    {
        $this->authorize('view', $agent);

        $referralCode = $agent->referralCode;

        return view('crm.agents.referral', compact('agent', 'referralCode'));
    }
}
