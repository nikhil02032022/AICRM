<?php

declare(strict_types=1);

// BRD: CRM-AG-002 — Referral link attribution feature tests

use App\Enums\CRM\LeadSource;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentReferralCode;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Agents\AgentReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

describe('Agent Referral Attribution (CRM-AG-002)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create(['name' => 'ABC Institute', 'code' => 'ABC']);
        $this->agent       = Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Ref Agent',
            'email'           => 'ref@agent.com',
            'password'        => bcrypt('password'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);
        $this->referralCode = AgentReferralCode::withoutGlobalScopes()->create([
            'agent_id'       => $this->agent->id,
            'institution_id' => $this->institution->id,
            'code'           => 'ABCINS-AG-F1A2',
            'url_slug'       => 'abcins-ag-f1a2',
        ]);

        $this->service = app(AgentReferralService::class);
    });

    it('resolves correct agent from valid ?ref param', function () {
        $request  = Request::create('/?ref=ABCINS-AG-F1A2');
        $resolved = $this->service->resolveFromRequest($request);

        expect($resolved)->not->toBeNull();
        expect($resolved->id)->toBe($this->agent->id);
    });

    it('returns null for invalid ?ref param', function () {
        $request  = Request::create('/?ref=BAD-CODE-999');
        $resolved = $this->service->resolveFromRequest($request);

        expect($resolved)->toBeNull();
    });

    it('returns null when ?ref param is absent', function () {
        $request  = Request::create('/');
        $resolved = $this->service->resolveFromRequest($request);

        expect($resolved)->toBeNull();
    });

    it('attributing lead updates agent_id and source and increments lead count', function () {
        $lead = Lead::withoutGlobalScopes()->create([
            'institution_id' => $this->institution->id,
            'first_name'     => 'Priya', 'last_name' => 'Singh',
            'email'          => encrypt('priya@example.com'),
            'mobile'         => encrypt('9100000001'),
            'source'         => 'walk_in', 'status' => 'new_enquiry', 'consent_given' => true,
        ]);

        $this->service->attributeLead($lead, $this->agent);

        $lead->refresh();
        expect($lead->agent_id)->toBe($this->agent->id);
        expect($lead->source)->toBe(LeadSource::AGENT);

        $this->referralCode->refresh();
        expect($this->referralCode->total_leads)->toBe(1);
    });
});
