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

function makeInstitutionAndApplicationApiUser(string $code): array
{
    $institution = Institution::create([
        'name' => 'Application API Test '.$code,
        'code' => $code,
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Application API User '.$code,
        'email' => strtolower($code).'@api.test',
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

function makeLead(
    int $institutionId,
    string $suffix = '01',
    LeadSource $source = LeadSource::WALK_IN,
    int $leadScore = 0,
    ?string $preferredIntake = null,
): Lead
{
    return Lead::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'first_name' => 'Applicant'.$suffix,
        'last_name' => 'Test',
        'mobile' => '98765432'.$suffix,
        'email' => 'applicant'.$suffix.'@example.test',
        'source' => $source,
        'lead_score' => $leadScore,
        'preferred_intake' => $preferredIntake,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1-test',
    ]);
}

function makeProgramme(int $institutionId, string $name): CrmProgramme
{
    return CrmProgramme::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'name' => $name,
        'code' => strtoupper(str_replace(' ', '-', $name)),
        'is_active' => true,
    ]);
}

function attachProgrammeInterest(Lead $lead, CrmProgramme $programme, ?string $preferredIntake = null): void
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

function makeTemplateAndDraft(int $institutionId, ?int $createdBy = null): array
{
    $template = ApplicationFormTemplate::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'name' => 'AP-008 Template',
        'slug' => 'ap-008-template-'.fake()->unique()->numerify('###'),
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
        'settings' => [
            'allow_save_and_resume' => true,
            'mobile_optimised' => true,
        ],
        'minimum_completeness_percentage' => 80,
        'is_active' => true,
        'created_by' => $createdBy,
    ]);

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'application_form_template_id' => $template->id,
        'resume_token' => 'resume-'.fake()->unique()->numerify('######'),
        'status' => 'submitted',
        'progress_percentage' => 100,
        'form_data' => ['personal_details' => ['first_name' => 'Applicant']],
        'last_saved_at' => now(),
        'submitted_at' => now(),
        'expires_at' => now()->addDays(7),
        'created_by' => $createdBy,
    ]);

    return [$template, $draft];
}

function makeApplication(
    int $institutionId,
    string $status = 'under_review',
    ?int $assignedCounsellorId = null,
    string $suffix = '01',
    LeadSource $source = LeadSource::WALK_IN,
    int $leadScore = 0,
    ?string $preferredIntake = null,
): Application {
    $lead = makeLead($institutionId, $suffix, $source, $leadScore, $preferredIntake);
    [, $draft] = makeTemplateAndDraft($institutionId);

    return Application::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'lead_uuid' => $lead->uuid,
        'application_form_draft_uuid' => $draft->uuid,
        'assigned_counsellor_id' => $assignedCounsellorId,
        'status' => $status,
        'stage_entered_at' => now(),
        'submitted_at' => now(),
    ]);
}

test('lists applications with standard envelope and pagination for AP-008', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationApiUser('AP008A01');

    makeApplication($institution->id, 'under_review', null, '11');
    makeApplication($institution->id, 'shortlisted', null, '12');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/applications?per_page=10');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'data',
            'meta' => ['total', 'per_page', 'current_page', 'last_page'],
        ])
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 2);
});

test('filters applications by status for AP-009', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationApiUser('AP008A02');

    makeApplication($institution->id, 'under_review', null, '21');
    makeApplication($institution->id, 'shortlisted', null, '22');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/applications?status=shortlisted');

    $response->assertOk()
        ->assertJsonPath('meta.total', 1);
});

test('filters applications by programme batch source and score for AP-009', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationApiUser('AP008A10');

    $programmeA = makeProgramme($institution->id, 'B.Tech CSE');
    $programmeB = makeProgramme($institution->id, 'MBA');

    $applicationA = makeApplication(
        $institution->id,
        'under_review',
        $user->id,
        '81',
        LeadSource::FACEBOOK,
        88,
        '26FALL',
    );
    $applicationB = makeApplication(
        $institution->id,
        'under_review',
        $user->id,
        '82',
        LeadSource::WALK_IN,
        45,
        '25SPRING',
    );

    attachProgrammeInterest($applicationA->lead, $programmeA, '26FALL');
    attachProgrammeInterest($applicationB->lead, $programmeB, '25SPRING');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/applications?programme_id='.$programmeA->id.'&batch=26FALL&source=facebook&score_min=80&score_max=90');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.uuid', $applicationA->uuid);
});

test('shows single application by uuid for AP-008', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationApiUser('AP008A03');

    $application = makeApplication($institution->id, 'under_review', null, '31');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/applications/'.$application->uuid)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.uuid', $application->uuid)
        ->assertJsonPath('data.status', ApplicationStatus::UNDER_REVIEW->value);
});

test('enforces tenant isolation for application show endpoint', function (): void {
    [$institutionA, $userA] = makeInstitutionAndApplicationApiUser('AP008A04');
    [, $userB] = makeInstitutionAndApplicationApiUser('AP008A05');

    $applicationA = makeApplication($institutionA->id, 'under_review', null, '41');

    $this->actingAs($userB, 'sanctum')
        ->getJson('/api/v1/crm/applications/'.$applicationA->uuid)
        ->assertNotFound();
});

test('transitions application from under_review to shortlisted for AP-009', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationApiUser('AP008A06');
    $application = makeApplication($institution->id, 'under_review', $user->id, '51');

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/applications/'.$application->uuid.'/transition', [
            'status' => ApplicationStatus::SHORTLISTED->value,
            'reason' => 'Eligible after review',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', ApplicationStatus::SHORTLISTED->value);

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

test('rejects invalid transition for AP-009 state machine', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationApiUser('AP008A07');
    $application = makeApplication($institution->id, 'under_review', $user->id, '61');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/applications/'.$application->uuid.'/transition', [
            'status' => ApplicationStatus::ENROLLED->value,
            'reason' => 'Invalid jump',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'INVALID_TRANSITION');
});

test('returns seat availability payload for AP-011', function (): void {
    [, $user] = makeInstitutionAndApplicationApiUser('AP008A08');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/programmes/'.fake()->uuid().'/seat-availability')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'data' => ['programme_uuid', 'total_seats', 'allocated_seats', 'available_seats'],
        ]);
});

test('returns conversion funnel analytics payload for AP-018 and AP-019', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationApiUser('AP008A09');

    makeApplication($institution->id, 'under_review', null, '71');
    makeApplication($institution->id, 'shortlisted', null, '72');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/applications/analytics/funnel')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'data']);
});
