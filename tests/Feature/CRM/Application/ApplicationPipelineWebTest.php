<?php

declare(strict_types=1);

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\LeadSource;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationFormDraft;
use App\Models\CRM\ApplicationFormTemplate;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeInstitutionAndApplicationWebUserForPipeline(string $code): array
{
    $institution = Institution::create([
        'name' => 'Application Web Pipeline '.$code,
        'code' => $code,
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Application Pipeline User '.$code,
        'email' => strtolower($code).'@web.test',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo([
        'crm.applications.view',
        'crm.applications.create',
        'crm.applications.edit',
        'crm.applications.delete',
    ]);

    return [$institution, $user];
}

function makePipelineApplication(
    int $institutionId,
    ?int $assignedCounsellorId = null,
    string $firstName = 'Pipeline',
    string $mobile = '9876500001',
    string $email = 'pipeline-applicant@example.test',
    LeadSource $source = LeadSource::WALK_IN,
    int $leadScore = 0,
    ?string $preferredIntake = null,
): Application
{
    $lead = Lead::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'first_name' => $firstName,
        'last_name' => 'Applicant',
        'mobile' => $mobile,
        'email' => $email,
        'source' => $source,
        'lead_score' => $leadScore,
        'preferred_intake' => $preferredIntake,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1-test',
    ]);

    $template = ApplicationFormTemplate::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'name' => 'Web AP-008 Template',
        'slug' => 'web-ap-008-template-'.fake()->unique()->numerify('###'),
        'sections' => [
            [
                'id' => 'personal_details',
                'title' => 'Personal Details',
                'order' => 1,
                'fields' => [
                    ['id' => 'first_name', 'type' => 'text', 'label' => 'First Name', 'required' => true],
                ],
            ],
        ],
        'settings' => ['allow_save_and_resume' => true, 'mobile_optimised' => true],
        'minimum_completeness_percentage' => 80,
        'is_active' => true,
    ]);

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'application_form_template_id' => $template->id,
        'resume_token' => 'resume-'.fake()->unique()->numerify('######'),
        'status' => 'submitted',
        'progress_percentage' => 100,
        'form_data' => ['personal_details' => ['first_name' => 'Pipeline']],
        'last_saved_at' => now(),
        'submitted_at' => now(),
        'expires_at' => now()->addDays(7),
    ]);

    return Application::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'lead_uuid' => $lead->uuid,
        'application_form_draft_uuid' => $draft->uuid,
        'assigned_counsellor_id' => $assignedCounsellorId,
        'status' => 'under_review',
        'stage_entered_at' => now(),
        'submitted_at' => now(),
    ]);
}

function makeProgrammeForPipelineWeb(int $institutionId, string $name): CrmProgramme
{
    return CrmProgramme::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'name' => $name,
        'code' => strtoupper(str_replace(' ', '-', $name)),
        'is_active' => true,
    ]);
}

function attachProgrammeForPipelineWeb(Lead $lead, CrmProgramme $programme, ?string $preferredIntake = null): void
{
    DB::table('lead_programme_interests')->insert([
        'lead_id' => $lead->id,
        'crm_programme_id' => $programme->id,
        'is_primary' => true,
        'status' => 'interested',
        'notes' => null,
        'preferred_intake' => $preferredIntake,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('renders pipeline board page for AP-008 web flow', function (): void {
    [, $user] = makeInstitutionAndApplicationWebUserForPipeline('AP008W01');

    $this->actingAs($user)
        ->get(route('crm.applications.pipeline.board'))
        ->assertOk()
        ->assertViewIs('crm.applications.pipeline.board')
        ->assertSeeText('Application Pipeline');
});

test('renders pipeline list page for AP-008 web flow', function (): void {
    [, $user] = makeInstitutionAndApplicationWebUserForPipeline('AP008W02');

    $this->actingAs($user)
        ->get(route('crm.applications.list'))
        ->assertOk()
        ->assertViewIs('crm.applications.pipeline.list')
        ->assertSeeText('Application List');
});

test('filters list page by AP-009 filter set', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationWebUserForPipeline('AP008W08');

    $programmeA = makeProgrammeForPipelineWeb($institution->id, 'B.Com');
    $programmeB = makeProgrammeForPipelineWeb($institution->id, 'MBA');

    $matchingApplication = makePipelineApplication(
        $institution->id,
        $user->id,
        'FilterMatch',
        '9876500011',
        'filter-match@example.test',
        LeadSource::FACEBOOK,
        91,
        '26FALL',
    );
    $otherApplication = makePipelineApplication(
        $institution->id,
        $user->id,
        'FilterOther',
        '9876500012',
        'filter-other@example.test',
        LeadSource::WALK_IN,
        35,
        '25SPRING',
    );

    attachProgrammeForPipelineWeb($matchingApplication->lead, $programmeA, '26FALL');
    attachProgrammeForPipelineWeb($otherApplication->lead, $programmeB, '25SPRING');

    $fromDate = now()->subDay()->toDateString();
    $toDate = now()->addDay()->toDateString();

    $this->actingAs($user)
        ->get(route('crm.applications.list', [
            'programme_id' => $programmeA->id,
            'batch' => '26FALL',
            'source' => LeadSource::FACEBOOK->value,
            'status' => ApplicationStatus::UNDER_REVIEW->value,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'score_min' => 90,
            'score_max' => 95,
        ]))
        ->assertOk()
        ->assertSeeText('FilterMatch')
        ->assertDontSeeText('FilterOther');
});

test('renders application detail page for AP-008 web flow', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationWebUserForPipeline('AP008W03');
    $application = makePipelineApplication($institution->id, $user->id);

    $this->actingAs($user)
        ->get(route('crm.applications.show', ['application' => $application->uuid]))
        ->assertOk()
        ->assertViewIs('crm.applications.pipeline.show')
        ->assertSeeText('Pipeline Applicant')
        ->assertSeeText((string) $application->uuid);
});

test('renders transition form page for AP-009 web action', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationWebUserForPipeline('AP008W04');
    $application = makePipelineApplication($institution->id, $user->id);

    $this->actingAs($user)
        ->post(route('crm.applications.transition', ['application' => $application->uuid]))
        ->assertOk()
        ->assertViewIs('crm.applications.pipeline.modals.transition-form')
        ->assertSeeText('Transition Application');
});

test('executes AP-009 web transition and writes history', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationWebUserForPipeline('AP008W07');
    $application = makePipelineApplication($institution->id, $user->id);

    $this->actingAs($user)
        ->post(route('crm.applications.transition.apply', ['application' => $application->uuid]), [
            'status' => ApplicationStatus::SHORTLISTED->value,
            'reason' => 'Eligible after review',
        ])
        ->assertRedirect(route('crm.applications.show', ['application' => $application->uuid]));

    $this->assertDatabaseHas('applications', [
        'uuid' => $application->uuid,
        'status' => ApplicationStatus::SHORTLISTED->value,
    ]);

    $this->assertDatabaseHas('application_status_history', [
        'application_uuid' => $application->uuid,
        'from_status' => ApplicationStatus::UNDER_REVIEW->value,
        'to_status' => ApplicationStatus::SHORTLISTED->value,
        'reason' => 'Eligible after review',
    ]);
});

test('enforces tenant isolation on application detail page', function (): void {
    [$institutionA, $userA] = makeInstitutionAndApplicationWebUserForPipeline('AP008W05');
    [, $userB] = makeInstitutionAndApplicationWebUserForPipeline('AP008W06');

    $applicationA = makePipelineApplication($institutionA->id, $userA->id);

    $this->actingAs($userB)
        ->get(route('crm.applications.show', ['application' => $applicationA->uuid]))
        ->assertNotFound();
});
