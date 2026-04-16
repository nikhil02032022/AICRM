<?php

declare(strict_types=1);

use App\Enums\CRM\ApplicationFormDraftStatus;
use App\Models\CRM\ApplicationFormDraft;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\ApplicationFormTemplate;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeInstitutionAndApplicationAdmin(string $code): array
{
    $institution = Institution::create([
        'name' => 'Application Draft API Test '.$code,
        'code' => $code,
        'is_active' => true,
    ]);

    $admin = User::create([
        'name' => 'Application Draft API Admin '.$code,
        'email' => strtolower($code).'@api.test',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $admin->givePermissionTo([
        'crm.applications.view',
        'crm.applications.create',
        'crm.applications.edit',
        'crm.applications.delete',
    ]);

    return [$institution, $admin];
}

function makeTemplate(
    int $institutionId,
    int $createdBy,
    bool $allowSaveAndResume = true,
    bool $mobileOptimised = true,
    bool $applicationFeeEnabled = false,
    float $applicationFeeAmount = 0.0,
    string $applicationFeeCurrency = 'INR',
    bool $allowMultiProgrammeApplications = false,
    int $maxProgrammesPerApplication = 1,
): ApplicationFormTemplate
{
    return ApplicationFormTemplate::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'name' => 'AP-003 Template',
        'slug' => 'ap-003-template-'.strtolower((string) fake()->unique()->numerify('###')),
        'sections' => [
            [
                'id' => 'personal_details',
                'title' => 'Personal Details',
                'order' => 1,
                'fields' => [
                    ['id' => 'first_name', 'type' => 'text', 'label' => 'First Name', 'required' => true],
                ],
            ],
            [
                'id' => 'academic_history',
                'title' => 'Academic History',
                'order' => 2,
                'fields' => [
                    ['id' => 'qualification', 'type' => 'text', 'label' => 'Qualification', 'required' => true],
                ],
            ],
            [
                'id' => 'entrance_exam_scores',
                'title' => 'Entrance Exam Scores',
                'order' => 3,
                'fields' => [
                    ['id' => 'exam_score', 'type' => 'number', 'label' => 'Score', 'required' => false],
                ],
            ],
            [
                'id' => 'co_curricular_activities',
                'title' => 'Co-curricular Activities',
                'order' => 4,
                'fields' => [
                    ['id' => 'activities', 'type' => 'textarea', 'label' => 'Activities', 'required' => false],
                ],
            ],
            [
                'id' => 'declarations',
                'title' => 'Declarations',
                'order' => 5,
                'fields' => [
                    ['id' => 'declaration_ack', 'type' => 'checkbox', 'label' => 'Declaration', 'required' => true],
                ],
            ],
            [
                'id' => 'digital_signature',
                'title' => 'Digital Signature',
                'order' => 6,
                'fields' => [
                    ['id' => 'applicant_signature', 'type' => 'signature', 'label' => 'Signature', 'required' => true],
                ],
            ],
        ],
        'settings' => [
            'allow_save_and_resume' => $allowSaveAndResume,
            'mobile_optimised' => $mobileOptimised,
            'show_progress_bar' => true,
            'application_fee_enabled' => $applicationFeeEnabled,
            'application_fee_amount' => $applicationFeeAmount,
            'application_fee_currency' => $applicationFeeCurrency,
            'allow_multi_programme_applications' => $allowMultiProgrammeApplications,
            'max_programmes_per_application' => $maxProgrammesPerApplication,
        ],
        'minimum_completeness_percentage' => 80,
        'is_active' => true,
        'created_by' => $createdBy,
    ]);
}

/** @return list<string> */
function makeProgrammes(int $institutionId, int $count = 3): array
{
    $uuids = [];

    for ($i = 1; $i <= $count; $i++) {
        $programme = CrmProgramme::withoutGlobalScopes()->create([
            'institution_id' => $institutionId,
            'name' => 'Programme '.$i,
            'code' => 'PRG'.$i,
            'level' => 'UG',
            'department' => 'Admissions',
            'is_active' => true,
            'erp_programme_uuid' => (string) fake()->uuid(),
        ]);

        $uuids[] = (string) $programme->erp_programme_uuid;
    }

    return $uuids;
}

it('creates application form draft via api when template allows save and resume', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APDRAFT01');
    $template = makeTemplate($institution->id, $admin->id, true);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates/'.$template->uuid.'/drafts', [
            'current_section_id' => 'personal_details',
            'last_completed_section_order' => 1,
            'progress_percentage' => 15,
            'form_data' => [
                'personal_details' => [
                    'first_name' => 'Aarav',
                ],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', ApplicationFormDraftStatus::DRAFT->value)
        ->assertJsonPath('data.current_section_id', 'personal_details');

    $this->assertDatabaseHas('application_form_drafts', [
        'institution_id' => $institution->id,
        'application_form_template_id' => $template->id,
        'status' => ApplicationFormDraftStatus::DRAFT->value,
        'progress_percentage' => 15,
    ]);
});

it('rejects draft creation when template does not allow save and resume', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APDRAFT02');
    $template = makeTemplate($institution->id, $admin->id, false);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates/'.$template->uuid.'/drafts', [
            'current_section_id' => 'personal_details',
            'progress_percentage' => 10,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['settings.allow_save_and_resume']);
});

it('updates draft progress and payload via api', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APDRAFT03');
    $template = makeTemplate($institution->id, $admin->id, true);

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'resume-token-1',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'personal_details',
        'progress_percentage' => 20,
        'form_data' => ['personal_details' => ['first_name' => 'Aarav']],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(7),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/crm/application-form-drafts/'.$draft->uuid, [
            'current_section_id' => 'academic_history',
            'last_completed_section_order' => 2,
            'progress_percentage' => 42,
            'form_data' => [
                'personal_details' => ['first_name' => 'Aarav'],
                'academic_history' => ['qualification' => 'BSc'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.current_section_id', 'academic_history')
        ->assertJsonPath('data.progress_percentage', 42)
        ->assertJsonPath('data.form_data.academic_history.qualification', 'BSc');
});

it('resumes draft by token within same institution', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APDRAFT04');
    $template = makeTemplate($institution->id, $admin->id, true);

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'resume-token-allowed',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'declarations',
        'progress_percentage' => 80,
        'form_data' => ['declarations' => ['declaration_ack' => true]],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(5),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/application-form-drafts/resume/'.$draft->resume_token)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.uuid', $draft->uuid)
        ->assertJsonPath('data.resume_token', 'resume-token-allowed');
});

it('does not resume draft from another institution', function (): void {
    [$institutionA, $adminA] = makeInstitutionAndApplicationAdmin('APDRAFT05');
    [, $adminB] = makeInstitutionAndApplicationAdmin('APDRAFT06');
    $template = makeTemplate($institutionA->id, $adminA->id, true);

    ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institutionA->id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'cross-tenant-token',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'personal_details',
        'progress_percentage' => 25,
        'form_data' => ['personal_details' => ['first_name' => 'Riya']],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(3),
        'created_by' => $adminA->id,
    ]);

    $this->actingAs($adminB, 'sanctum')
        ->getJson('/api/v1/crm/application-form-drafts/resume/cross-tenant-token')
        ->assertNotFound();
});

it('submits draft when progress meets minimum completeness threshold', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APDRAFT07');
    $template = makeTemplate($institution->id, $admin->id, true);

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'submit-token-ok',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'digital_signature',
        'progress_percentage' => 80,
        'form_data' => ['digital_signature' => ['applicant_signature' => 'base64-signature']],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(2),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-drafts/'.$draft->uuid.'/submit', [
            'progress_percentage' => 92,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', ApplicationFormDraftStatus::SUBMITTED->value)
        ->assertJsonPath('data.progress_percentage', 92);

    $this->assertDatabaseHas('application_form_drafts', [
        'id' => $draft->id,
        'status' => ApplicationFormDraftStatus::SUBMITTED->value,
        'progress_percentage' => 92,
    ]);
});

it('rejects draft submission below template minimum completeness threshold', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APDRAFT08');
    $template = makeTemplate($institution->id, $admin->id, true);

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'submit-token-low',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'co_curricular_activities',
        'progress_percentage' => 40,
        'form_data' => ['co_curricular_activities' => ['activities' => 'Sports']],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(2),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-drafts/'.$draft->uuid.'/submit', [
            'progress_percentage' => 50,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['progress_percentage']);

    $this->assertDatabaseHas('application_form_drafts', [
        'id' => $draft->id,
        'status' => ApplicationFormDraftStatus::DRAFT->value,
    ]);
});

it('requires application fee payment before submission when AP-004 fee is enabled', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APDRAFT09');
    $template = makeTemplate(
        institutionId: $institution->id,
        createdBy: $admin->id,
        allowSaveAndResume: true,
        mobileOptimised: true,
        applicationFeeEnabled: true,
        applicationFeeAmount: 1500.00,
        applicationFeeCurrency: 'INR',
    );

    $createResponse = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates/'.$template->uuid.'/drafts', [
            'current_section_id' => 'personal_details',
            'last_completed_section_order' => 1,
            'progress_percentage' => 30,
            'form_data' => [
                'personal_details' => ['first_name' => 'Aarav'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.application_fee_status', 'pending')
        ->assertJsonPath('data.application_fee_currency', 'INR')
        ->assertJsonPath('data.application_fee_amount', '1500.00');

    $draftUuid = (string) $createResponse->json('data.uuid');

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-drafts/'.$draftUuid.'/submit', [
            'progress_percentage' => 85,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['application_fee_status']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-drafts/'.$draftUuid.'/fee/pay', [
            'gateway' => 'online',
            'transaction_reference' => 'APFEE-TEST-1001',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.application_fee_status', 'paid')
        ->assertJsonPath('data.application_fee_transaction_reference', 'APFEE-TEST-1001');

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-drafts/'.$draftUuid.'/submit', [
            'progress_percentage' => 90,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', ApplicationFormDraftStatus::SUBMITTED->value);
});

it('supports simultaneous multi-programme application selection for AP-005', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APDRAFT10');
    $programmeUuids = makeProgrammes($institution->id, 3);
    $template = makeTemplate(
        institutionId: $institution->id,
        createdBy: $admin->id,
        allowSaveAndResume: true,
        allowMultiProgrammeApplications: true,
        maxProgrammesPerApplication: 3,
    );

    $createResponse = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates/'.$template->uuid.'/drafts', [
            'current_section_id' => 'personal_details',
            'last_completed_section_order' => 1,
            'progress_percentage' => 25,
            'programme_uuids' => [$programmeUuids[0], $programmeUuids[1]],
            'form_data' => [
                'personal_details' => ['first_name' => 'Aarav'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.selected_programme_uuids.0', $programmeUuids[0])
        ->assertJsonPath('data.selected_programme_uuids.1', $programmeUuids[1]);

    $draftUuid = (string) $createResponse->json('data.uuid');

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-drafts/'.$draftUuid.'/submit', [
            'progress_percentage' => 85,
            'programme_uuids' => [$programmeUuids[0], $programmeUuids[2]],
        ])
        ->assertOk()
        ->assertJsonPath('data.status', ApplicationFormDraftStatus::SUBMITTED->value)
        ->assertJsonPath('data.selected_programme_uuids.0', $programmeUuids[0])
        ->assertJsonPath('data.selected_programme_uuids.1', $programmeUuids[2]);
});

it('rejects draft creation when template is not mobile optimised for AP-006', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APDRAFT11');
    $template = makeTemplate(
        institutionId: $institution->id,
        createdBy: $admin->id,
        allowSaveAndResume: true,
        mobileOptimised: false,
    );

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates/'.$template->uuid.'/drafts', [
            'current_section_id' => 'personal_details',
            'progress_percentage' => 10,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['settings.mobile_optimised']);
});

it('enforces AP-007 mandatory fields during submission for submitted sections', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APDRAFT12');
    $template = makeTemplate($institution->id, $admin->id, true, true);

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'submit-token-ap007-required',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'digital_signature',
        'progress_percentage' => 85,
        'application_fee_status' => 'not_required',
        'form_data' => [
            'personal_details' => [
                'first_name' => 'Aarav',
            ],
            'digital_signature' => [
                'applicant_signature' => 'signed',
            ],
        ],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(2),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-drafts/'.$draft->uuid.'/submit', [
            'progress_percentage' => 90,
            'form_data' => [
                'digital_signature' => [
                    'applicant_signature' => '',
                ],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['form_data.digital_signature.applicant_signature']);
});
