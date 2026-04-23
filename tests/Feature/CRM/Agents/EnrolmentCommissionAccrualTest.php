<?php

declare(strict_types=1);

// BRD: CRM-AG-005 — EnrolmentCommissionObserver feature tests: observer fires on ENROLLED transition

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Agents\Agent;
use App\Models\CRM\Agents\AgentCommissionAccrual;
use App\Models\CRM\Agents\AgentCommissionStructure;
use App\Models\CRM\Agents\AgentReferralCode;
use App\Models\CRM\Application;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\CrmProgramme;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('EnrolmentCommissionObserver (CRM-AG-005)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->programme   = CrmProgramme::factory()->create(['institution_id' => $this->institution->id]);

        $this->agent = Agent::withoutGlobalScopes()->create([
            'institution_id'  => $this->institution->id,
            'name'            => 'Obs Agent',
            'email'           => 'obs@agent.com',
            'password'        => bcrypt('pass'),
            'agreement_start' => now()->toDateString(),
            'status'          => 'active',
        ]);
        AgentReferralCode::withoutGlobalScopes()->create([
            'agent_id' => $this->agent->id, 'institution_id' => $this->institution->id,
            'code' => 'OBS-AG-0001', 'url_slug' => 'obs-ag-0001',
        ]);
        AgentCommissionStructure::withoutGlobalScopes()->create([
            'agent_id'       => $this->agent->id,
            'programme_id'   => $this->programme->id,
            'institution_id' => $this->institution->id,
            'structure_type' => 'per_enrolment',
            'amount'         => 7500.00,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        $this->lead = Lead::withoutGlobalScopes()->create([
            'institution_id' => $this->institution->id,
            'first_name' => 'Obs', 'last_name' => 'Student',
            'email' => encrypt('obs@student.com'), 'mobile' => encrypt('9000000099'),
            'source' => 'agent', 'status' => 'new_enquiry',
            'agent_id' => $this->agent->id, 'consent_given' => true,
        ]);
    });

    it('creates accrual when application transitions to ENROLLED', function () {
        $application = Application::withoutGlobalScopes()->create([
            'institution_id'              => $this->institution->id,
            'lead_uuid'                   => $this->lead->uuid,
            'programme_id'                => $this->programme->id,
            'status'                      => ApplicationStatus::OFFER_ACCEPTED,
            'stage_entered_at'            => now(),
            'application_form_draft_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'submitted_at'               => now(),
        ]);

        expect(AgentCommissionAccrual::withoutGlobalScopes()->count())->toBe(0);

        // Transition to ENROLLED
        $application->update(['status' => ApplicationStatus::ENROLLED]);

        expect(AgentCommissionAccrual::withoutGlobalScopes()->count())->toBe(1);

        $accrual = AgentCommissionAccrual::withoutGlobalScopes()->first();
        expect((float)$accrual->commission_amount)->toBe(7500.0);
        expect($accrual->agent_id)->toBe($this->agent->id);
        expect($accrual->status->value)->toBe('pending');
    });

    it('does not create accrual for other status transitions', function () {
        $application = Application::withoutGlobalScopes()->create([
            'institution_id'              => $this->institution->id,
            'lead_uuid'                   => $this->lead->uuid,
            'programme_id'                => $this->programme->id,
            'status'                      => ApplicationStatus::UNDER_REVIEW,
            'stage_entered_at'            => now(),
            'application_form_draft_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'submitted_at'               => now(),
        ]);

        $application->update(['status' => ApplicationStatus::SHORTLISTED]);
        $application->update(['status' => ApplicationStatus::OFFER_ISSUED]);
        $application->update(['status' => ApplicationStatus::OFFER_ACCEPTED]);

        expect(AgentCommissionAccrual::withoutGlobalScopes()->count())->toBe(0);
    });

    it('does not create duplicate accrual if observer fires again on already-enrolled app', function () {
        $application = Application::withoutGlobalScopes()->create([
            'institution_id'              => $this->institution->id,
            'lead_uuid'                   => $this->lead->uuid,
            'programme_id'                => $this->programme->id,
            'status'                      => ApplicationStatus::OFFER_ACCEPTED,
            'stage_entered_at'            => now(),
            'application_form_draft_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'submitted_at'               => now(),
        ]);

        $application->update(['status' => ApplicationStatus::ENROLLED]);
        // Observer should skip because original is already ENROLLED
        $application->update(['notes' => 'some other field change']);

        expect(AgentCommissionAccrual::withoutGlobalScopes()->count())->toBe(1);
    });
});
