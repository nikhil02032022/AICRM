<?php

declare(strict_types=1);

// BRD: CRM-AG-007 — AgentReportService unit tests: aggregation correctness

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentCommissionAccrual;
use App\Models\CRM\Agents\AgentReferralCode;
use App\Models\CRM\Application;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\CrmProgramme;
use App\Services\CRM\Agents\AgentReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('AgentReportService (CRM-AG-007)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->programme   = CrmProgramme::factory()->create(['institution_id' => $this->institution->id]);

        $this->agent = Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Report Agent',
            'email'           => 'report@agent.com',
            'password'        => bcrypt('password'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);
        AgentReferralCode::withoutGlobalScopes()->create([
            'agent_id' => $this->agent->id, 'institution_id' => $this->institution->id,
            'code' => 'REP-AG-0001', 'url_slug' => 'rep-ag-0001',
        ]);

        $this->service = app(AgentReportService::class);

        $this->makeLeads = function (int $count): array {
            $leads = [];
            for ($i = 0; $i < $count; $i++) {
                $leads[] = Lead::withoutGlobalScopes()->create([
                    'institution_id' => $this->institution->id,
                    'first_name'     => "Lead{$i}",
                    'last_name'      => 'Test',
                    'email'          => encrypt("lead{$i}@test.com"),
                    'mobile'         => encrypt("900000000{$i}"),
                    'source'         => 'agent',
                    'status'         => 'new_enquiry',
                    'agent_id'       => $this->agent->id,
                    'consent_given'  => true,
                ]);
            }
            return $leads;
        };
    });

    it('counts total leads correctly', function () {
        ($this->makeLeads)(3);

        $metrics = $this->service->forAgent($this->agent);

        expect($metrics['total_leads'])->toBe(3);
    });

    it('counts conversions (enrolled applications) correctly', function () {
        $leads = ($this->makeLeads)(2);

        Application::withoutGlobalScopes()->create([
            'institution_id'           => $this->institution->id,
            'lead_uuid'                => $leads[0]->uuid,
            'programme_id'             => $this->programme->id,
            'status'                   => ApplicationStatus::ENROLLED,
            'stage_entered_at'         => now(),
            'application_form_draft_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'submitted_at'               => now(),
        ]);

        $metrics = $this->service->forAgent($this->agent);

        expect($metrics['total_conversions'])->toBe(1);
        expect($metrics['conversion_rate'])->toBe(50.0);
    });

    it('aggregates commission amounts by status correctly', function () {
        $leads = ($this->makeLeads)(2);

        $app1 = Application::withoutGlobalScopes()->create([
            'institution_id'              => $this->institution->id,
            'lead_uuid'                   => $leads[0]->uuid,
            'programme_id'                => $this->programme->id,
            'status'                      => ApplicationStatus::ENROLLED,
            'stage_entered_at'            => now(),
            'application_form_draft_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'submitted_at'               => now(),
        ]);
        $app2 = Application::withoutGlobalScopes()->create([
            'institution_id'              => $this->institution->id,
            'lead_uuid'                   => $leads[1]->uuid,
            'programme_id'                => $this->programme->id,
            'status'                      => ApplicationStatus::ENROLLED,
            'stage_entered_at'            => now(),
            'application_form_draft_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'submitted_at'               => now(),
        ]);

        AgentCommissionAccrual::withoutGlobalScopes()->create([
            'institution_id'       => $this->institution->id,
            'agent_id'             => $this->agent->id,
            'application_id'       => $app1->id,
            'lead_id'              => $leads[0]->id,
            'programme_id'         => $this->programme->id,
            'commission_amount'    => 5000.00, 'status' => 'pending',
            'accrual_basis_amount' => 0, 'accrued_at' => now(),
        ]);
        AgentCommissionAccrual::withoutGlobalScopes()->create([
            'institution_id'       => $this->institution->id,
            'agent_id'             => $this->agent->id,
            'application_id'       => $app2->id,
            'lead_id'              => $leads[1]->id,
            'programme_id'         => $this->programme->id,
            'commission_amount'    => 3000.00, 'status' => 'paid',
            'accrual_basis_amount' => 0, 'accrued_at' => now(),
        ]);

        $metrics = $this->service->forAgent($this->agent);

        expect($metrics['total_accrued_commission'])->toBe(8000.0);
        expect($metrics['pending_commission'])->toBe(5000.0);
        expect($metrics['paid_commission'])->toBe(3000.0);
        expect($metrics['approved_commission'])->toBe(0.0);
    });
});
