<?php

declare(strict_types=1);

// BRD: CRM-FM-008 — Approval lifecycle across counsellor → manager → finance stages.

use App\Enums\CRM\Scholarships\ApprovalStage;
use App\Enums\CRM\Scholarships\ScholarshipAwardStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Scholarships\ScholarshipAward;
use App\Models\CRM\Scholarships\ScholarshipCategory;
use App\Services\CRM\Scholarships\ScholarshipAwardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeAwardFixtures(): ScholarshipAward
{
    $institution = Institution::factory()->create();
    $programme = CrmProgramme::factory()->for($institution)->create();
    $lead = Lead::factory()->for($institution)->create();
    $application = Application::factory()
        ->for($lead, 'lead')
        ->for($institution)
        ->create(['programme_id' => $programme->id]);
    $cat = ScholarshipCategory::factory()->create([
        'institution_id' => $institution->id,
        'programme_id' => $programme->id,
    ]);

    return ScholarshipAward::factory()->create([
        'institution_id' => $institution->id,
        'application_uuid' => $application->uuid,
        'scholarship_category_id' => $cat->id,
        'amount' => 15000,
        'status' => ScholarshipAwardStatus::DRAFT->value,
        'current_stage' => ApprovalStage::COUNSELLOR->value,
    ]);
}

it('advances through all three stages on the happy path', function () {
    $award = makeAwardFixtures();
    $service = app(ScholarshipAwardService::class);

    $service->submit($award);
    $award->refresh();
    expect($award->status)->toBe(ScholarshipAwardStatus::COUNSELLOR_SUBMITTED);
    expect($award->current_stage)->toBe(ApprovalStage::MANAGER);

    $service->approve($award, ApprovalStage::MANAGER, 'looks good');
    $award->refresh();
    expect($award->status)->toBe(ScholarshipAwardStatus::MANAGER_APPROVED);
    expect($award->current_stage)->toBe(ApprovalStage::FINANCE);

    $service->approve($award, ApprovalStage::FINANCE);
    $award->refresh();
    expect($award->status)->toBe(ScholarshipAwardStatus::FINANCE_APPROVED);
    expect($award->finance_approved_at)->not->toBeNull();
});

it('rejects at manager stage', function () {
    $award = makeAwardFixtures();
    $service = app(ScholarshipAwardService::class);
    $service->submit($award);

    $service->reject($award, ApprovalStage::MANAGER, 'not qualifying');
    $award->refresh();
    expect($award->status)->toBe(ScholarshipAwardStatus::REJECTED);
    expect($award->rejection_reason)->toBe('not qualifying');
});

it('refuses decision at wrong stage', function () {
    $award = makeAwardFixtures();
    $service = app(ScholarshipAwardService::class);
    $service->submit($award); // now at manager

    $this->expectException(DomainException::class);
    $service->approve($award, ApprovalStage::FINANCE);
});
