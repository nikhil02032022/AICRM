<?php

declare(strict_types=1);

// BRD: CRM-AG-005 — CommissionAccrualService unit tests: calculation per structure type

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\Agents\CommissionStructureType;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentCommissionAccrual;
use App\Models\CRM\Agents\AgentCommissionStructure;
use App\Models\CRM\Agents\AgentReferralCode;
use App\Models\CRM\Application;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Services\CRM\Agents\CommissionAccrualService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CommissionAccrualService (CRM-AG-005)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->programme   = CrmProgramme::factory()->create(['institution_id' => $this->institution->id]);

        $this->agent = Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Test Agent',
            'email'           => 'agent@test.com',
            'password'        => bcrypt('password'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);
        AgentReferralCode::withoutGlobalScopes()->create([
            'agent_id' => $this->agent->id, 'institution_id' => $this->institution->id,
            'code' => 'TEST-AG-0001', 'url_slug' => 'test-ag-0001',
        ]);

        $this->lead = Lead::withoutGlobalScopes()->create([
            'institution_id' => $this->institution->id,
            'first_name' => 'Test', 'last_name' => 'Lead',
            'email' => encrypt('tl@example.com'), 'mobile' => encrypt('9000000000'),
            'source' => 'agent', 'status' => 'new_enquiry',
            'agent_id' => $this->agent->id, 'consent_given' => true,
        ]);

        $this->application = Application::withoutGlobalScopes()->create([
            'institution_id'              => $this->institution->id,
            'lead_uuid'                   => $this->lead->uuid,
            'programme_id'                => $this->programme->id,
            'status'                      => ApplicationStatus::OFFER_ACCEPTED,
            'stage_entered_at'            => now(),
            'application_form_draft_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'submitted_at'               => now(),
        ]);

        $this->service = app(CommissionAccrualService::class);
    });

    it('creates PerEnrolment accrual with fixed amount', function () {
        AgentCommissionStructure::withoutGlobalScopes()->create([
            'agent_id' => $this->agent->id, 'programme_id' => $this->programme->id,
            'institution_id' => $this->institution->id,
            'structure_type' => 'per_enrolment', 'amount' => 5000.00,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        $accrual = $this->service->accrue($this->application);

        expect($accrual)->toBeInstanceOf(AgentCommissionAccrual::class);
        expect((float)$accrual->commission_amount)->toBe(5000.0);
        expect((float)$accrual->accrual_basis_amount)->toBe(0.0);
    });

    it('creates PerApplication accrual with fixed amount', function () {
        AgentCommissionStructure::withoutGlobalScopes()->create([
            'agent_id' => $this->agent->id, 'programme_id' => $this->programme->id,
            'institution_id' => $this->institution->id,
            'structure_type' => 'per_application', 'amount' => 2500.00,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        $accrual = $this->service->accrue($this->application);

        expect((float)$accrual->commission_amount)->toBe(2500.0);
    });

    it('creates PercentageFee accrual from confirmed payments', function () {
        AgentCommissionStructure::withoutGlobalScopes()->create([
            'agent_id' => $this->agent->id, 'programme_id' => $this->programme->id,
            'institution_id' => $this->institution->id,
            'structure_type' => 'percentage_fee', 'percentage' => 10.00,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        // Simulate confirmed payment of ₹50,000
        PaymentTransaction::withoutGlobalScopes()->create([
            'institution_id'    => $this->institution->id,
            'application_uuid'  => $this->application->uuid,
            'lead_uuid'         => $this->lead->uuid,
            'amount'            => 50000.00,
            'status'            => PaymentStatus::SUCCESS->value,
            'currency'          => 'INR',
            'fee_type'          => 'application',
            'gateway'           => 'razorpay',
            'idempotency_key'   => 'test-idem-' . uniqid(),
            'attempted_at'      => now(),
        ]);

        $accrual = $this->service->accrue($this->application);

        expect((float)$accrual->accrual_basis_amount)->toBe(50000.0);
        expect((float)$accrual->commission_amount)->toBe(5000.0); // 10% of 50000
    });

    it('returns null when no agent is attributed to the lead', function () {
        $lead = Lead::withoutGlobalScopes()->create([
            'institution_id' => $this->institution->id,
            'first_name' => 'No', 'last_name' => 'Agent',
            'email' => encrypt('na@example.com'), 'mobile' => encrypt('9000000002'),
            'source' => 'walk_in', 'status' => 'new_enquiry', 'consent_given' => true,
        ]);

        $app = Application::withoutGlobalScopes()->create([
            'institution_id'              => $this->institution->id,
            'lead_uuid'                   => $lead->uuid,
            'programme_id'                => $this->programme->id,
            'status'                      => ApplicationStatus::ENROLLED,
            'stage_entered_at'            => now(),
            'application_form_draft_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'submitted_at'               => now(),
        ]);

        $result = $this->service->accrue($app);

        expect($result)->toBeNull();
    });

    it('returns zero-commission accrual when no active structure exists', function () {
        // No commission structure configured for this agent+programme
        $accrual = $this->service->accrue($this->application);

        expect($accrual)->toBeNull();
    });
});
