<?php

declare(strict_types=1);

// BRD: CRM-AG-003 — Agent portal auth, lead submission, data isolation feature tests

use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentReferralCode;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Agents\AgentAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Agent Portal Lead Submission (CRM-AG-003)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create(['code' => 'INST01']);
        $this->agent       = Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Portal Agent',
            'email'           => 'portal@agent.com',
            'password'        => bcrypt('password123'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);
        AgentReferralCode::withoutGlobalScopes()->create([
            'agent_id' => $this->agent->id, 'institution_id' => $this->institution->id,
            'code' => 'INST01-AG-PRTA', 'url_slug' => 'inst01-ag-prta',
        ]);
    });

    it('unauthenticated request to dashboard redirects to login', function () {
        $response = $this->get('/agent-portal/dashboard');

        $response->assertRedirect('/agent-portal/login');
    });

    it('agent can log in with valid credentials', function () {
        $response = $this->post('/agent-portal/login', [
            'institution_code' => 'INST01',
            'email'            => 'portal@agent.com',
            'password'         => 'password123',
        ]);

        $response->assertRedirect('/agent-portal/dashboard');
        $response->assertCookieNotExpired('agent_portal_session');
    });

    it('login fails with wrong password', function () {
        $response = $this->post('/agent-portal/login', [
            'institution_code' => 'INST01',
            'email'            => 'portal@agent.com',
            'password'         => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertCookieMissing('agent_portal_session');
    });

    it('authenticated agent can submit a lead', function () {
        $plain    = app(AgentAuthService::class)->issueSession($this->agent);
        $response = $this->withCookie('agent_portal_session', $plain)
            ->post('/agent-portal/leads', [
                'first_name'    => 'Rahul',
                'last_name'     => 'Kumar',
                'email'         => 'rahul@example.com',
                'mobile'        => '9876543210',
                'consent_given' => '1',
            ]);

        $response->assertRedirect('/agent-portal/leads');

        $lead = Lead::withoutGlobalScopes()
            ->where('agent_id', $this->agent->id)
            ->latest()
            ->first();

        expect($lead)->not->toBeNull();
        expect($lead->agent_id)->toBe($this->agent->id);
    });

    it('agent cannot see leads submitted by another agent', function () {
        $otherAgent = Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Other Agent',
            'email'           => 'other@agent.com',
            'password'        => bcrypt('password'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);

        Lead::withoutGlobalScopes()->create([
            'institution_id' => $this->institution->id,
            'first_name' => 'Other', 'last_name' => 'Student',
            'email' => encrypt('other@student.com'), 'mobile' => encrypt('9000000001'),
            'source' => 'agent', 'status' => 'new_enquiry',
            'agent_id' => $otherAgent->id, 'consent_given' => true,
        ]);

        $plain    = app(AgentAuthService::class)->issueSession($this->agent);
        $response = $this->withCookie('agent_portal_session', $plain)->get('/agent-portal/leads');

        $response->assertOk();
        $response->assertDontSee('Other Student');
    });
});
