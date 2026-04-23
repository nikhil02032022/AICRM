<?php

declare(strict_types=1);

// BRD: CRM-AG-002 — AgentReferralService unit tests: code generation, attribution

use App\Enums\CRM\LeadSource;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentReferralCode;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Agents\AgentReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

describe('AgentReferralService (CRM-AG-002)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create(['name' => 'Test College', 'code' => 'TC001']);
        $this->agent       = Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Ravi Sharma',
            'email'           => 'ravi@example.com',
            'password'        => bcrypt('password'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);
        $this->service = app(AgentReferralService::class);
    });

    it('generates a referral code with institution prefix', function () {
        $referral = $this->service->generateCode($this->agent);

        expect($referral)->toBeInstanceOf(AgentReferralCode::class);
        expect($referral->code)->toMatch('/^TESTCO-AG-[0-9A-F]{4}$/');
        expect($referral->agent_id)->toBe($this->agent->id);
    });

    it('generates unique codes when collision occurs', function () {
        $first  = $this->service->generateCode($this->agent);
        expect($first->code)->not->toBeEmpty();

        $secondAgent = Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Priya Nair',
            'email'           => 'priya@example.com',
            'password'        => bcrypt('password'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);
        $second = $this->service->generateCode($secondAgent);

        expect($first->code)->not->toBe($second->code);
    });

    it('resolves agent from ?ref=CODE query param', function () {
        AgentReferralCode::withoutGlobalScopes()->create([
            'agent_id'       => $this->agent->id,
            'institution_id' => $this->institution->id,
            'code'           => 'TESTCO-AG-ABCD',
            'url_slug'       => 'testco-ag-abcd',
        ]);

        $request = Request::create('/?ref=TESTCO-AG-ABCD');
        $resolved = $this->service->resolveFromRequest($request);

        expect($resolved)->not->toBeNull();
        expect($resolved->id)->toBe($this->agent->id);
    });

    it('returns null for unknown referral code', function () {
        $request  = Request::create('/?ref=INVALID-CODE');
        $resolved = $this->service->resolveFromRequest($request);

        expect($resolved)->toBeNull();
    });

    it('attributes lead to agent and sets source to AGENT', function () {
        AgentReferralCode::withoutGlobalScopes()->create([
            'agent_id'       => $this->agent->id,
            'institution_id' => $this->institution->id,
            'code'           => 'TESTCO-AG-ATTR',
            'url_slug'       => 'testco-ag-attr',
            'total_leads'    => 0,
        ]);

        $lead = Lead::withoutGlobalScopes()->create([
            'institution_id' => $this->institution->id,
            'first_name'     => 'Anita',
            'last_name'      => 'Roy',
            'email'          => encrypt('anita@example.com'),
            'mobile'         => encrypt('9000000001'),
            'source'         => 'walk_in',
            'status'         => 'new_enquiry',
            'consent_given'  => true,
        ]);

        $this->service->attributeLead($lead, $this->agent);

        $lead->refresh();
        expect($lead->agent_id)->toBe($this->agent->id);
        expect($lead->source)->toBe(LeadSource::AGENT);

        $code = AgentReferralCode::withoutGlobalScopes()->where('agent_id', $this->agent->id)->first();
        expect($code->total_leads)->toBe(1);
    });
});
