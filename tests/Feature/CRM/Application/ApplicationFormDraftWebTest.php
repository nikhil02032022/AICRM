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

function makeInstitutionAndApplicationWebUser(string $code): array
{
    $institution = Institution::create([
        'name' => 'Application Web Test '.$code,
        'code' => $code,
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Application Web User '.$code,
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

function makeSaveResumeTemplate(
    int $institutionId,
    int $createdBy,
    bool $mobileOptimised = true,
    bool $applicationFeeEnabled = false,
    float $applicationFeeAmount = 0.0,
    bool $allowMultiProgrammeApplications = false,
    int $maxProgrammesPerApplication = 1,
): ApplicationFormTemplate
{
    return ApplicationFormTemplate::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'name' => 'Web AP-003 Template',
        'slug' => 'web-ap-003-template-'.strtolower((string) fake()->unique()->numerify('###')),
        'sections' => [
            [
                'id' => 'personal_details',
                'title' => 'Personal Details',
                'order' => 1,
                'fields' => [
                    ['id' => 'first_name', 'type' => 'text', 'label' => 'First Name', 'required' => true],
                    ['id' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
                ],
            ],
            [
                'id' => 'digital_signature',
                'title' => 'Digital Signature',
                'order' => 2,
                'fields' => [
                    ['id' => 'applicant_signature', 'type' => 'signature', 'label' => 'Applicant Signature', 'required' => true],
                ],
            ],
        ],
        'settings' => [
            'allow_save_and_resume' => true,
            'mobile_optimised' => $mobileOptimised,
            'show_progress_bar' => true,
            'application_fee_enabled' => $applicationFeeEnabled,
            'application_fee_amount' => $applicationFeeAmount,
            'application_fee_currency' => 'INR',
            'allow_multi_programme_applications' => $allowMultiProgrammeApplications,
            'max_programmes_per_application' => $maxProgrammesPerApplication,
        ],
        'minimum_completeness_percentage' => 80,
        'is_active' => true,
        'created_by' => $createdBy,
    ]);
}

/** @return list<string> */
function makeWebProgrammes(int $institutionId, int $count = 3): array
{
    $uuids = [];

    for ($i = 1; $i <= $count; $i++) {
        $programme = CrmProgramme::withoutGlobalScopes()->create([
            'institution_id' => $institutionId,
            'name' => 'Web Programme '.$i,
            'code' => 'WPRG'.$i,
            'level' => 'UG',
            'department' => 'Admissions',
            'is_active' => true,
            'erp_programme_uuid' => (string) fake()->uuid(),
        ]);

        $uuids[] = (string) $programme->erp_programme_uuid;
    }

    return $uuids;
}

it('opens web fill page for AP-003-enabled template', function (): void {
    [, $user] = makeInstitutionAndApplicationWebUser('APWDF01');
    $template = makeSaveResumeTemplate($user->institution_id, $user->id);

    $this->actingAs($user)
        ->get(route('crm.applications.forms.fill', $template->uuid))
        ->assertOk()
        ->assertSeeText('Application Fill')
        ->assertSeeText($template->name);
});

it('creates a draft from web fill start route', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationWebUser('APWDF02');
    $template = makeSaveResumeTemplate($institution->id, $user->id);

    $this->actingAs($user)
        ->post(route('crm.applications.forms.fill.save', $template->uuid), [
            'current_section_id' => 'personal_details',
            'last_completed_section_order' => 1,
            'progress_percentage' => 25,
            'form_data' => [
                'personal_details' => [
                    'first_name' => 'Aarav',
                    'email' => 'aarav@example.com',
                ],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('application_form_drafts', [
        'institution_id' => $institution->id,
        'application_form_template_id' => $template->id,
        'status' => ApplicationFormDraftStatus::DRAFT->value,
        'progress_percentage' => 25,
    ]);
});

it('saves and submits existing draft from web flow', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationWebUser('APWDF03');
    $template = makeSaveResumeTemplate($institution->id, $user->id);

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'web-resume-token-01',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'personal_details',
        'progress_percentage' => 35,
        'form_data' => [
            'personal_details' => [
                'first_name' => 'Aarav',
            ],
        ],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(7),
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('crm.applications.drafts.save', $draft->uuid), [
            'current_section_id' => 'digital_signature',
            'last_completed_section_order' => 2,
            'progress_percentage' => 82,
            'form_data' => [
                'personal_details' => [
                    'first_name' => 'Aarav',
                    'email' => 'aarav@example.com',
                ],
                'digital_signature' => [
                    'applicant_signature' => 'signed',
                ],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('application_form_drafts', [
        'id' => $draft->id,
        'progress_percentage' => 82,
    ]);

    $this->actingAs($user)
        ->post(route('crm.applications.drafts.submit', $draft->uuid), [
            'progress_percentage' => 85,
        ])
        ->assertRedirect(route('crm.applications.forms.index'));

    $this->assertDatabaseHas('application_form_drafts', [
        'id' => $draft->id,
        'status' => ApplicationFormDraftStatus::SUBMITTED->value,
    ]);
});

it('requires staff fee payment before AP-004 submission', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationWebUser('APWDF04');
    $template = makeSaveResumeTemplate(
        institutionId: $institution->id,
        createdBy: $user->id,
        mobileOptimised: true,
        applicationFeeEnabled: true,
        applicationFeeAmount: 1200.00,
    );

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'web-resume-token-fee',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'digital_signature',
        'progress_percentage' => 82,
        'application_fee_amount' => 1200,
        'application_fee_currency' => 'INR',
        'application_fee_status' => 'pending',
        'form_data' => [
            'digital_signature' => [
                'applicant_signature' => 'signed',
            ],
        ],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(7),
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('crm.applications.drafts.submit', $draft->uuid), [
            'progress_percentage' => 85,
        ])
        ->assertSessionHasErrors(['application_fee_status']);

    $this->actingAs($user)
        ->post(route('crm.applications.drafts.pay-fee', $draft->uuid), [
            'gateway' => 'online',
            'transaction_reference' => 'WEB-FEE-1001',
        ])
        ->assertRedirect(route('crm.applications.drafts.resume', $draft->uuid));

    $this->actingAs($user)
        ->post(route('crm.applications.drafts.submit', $draft->uuid), [
            'progress_percentage' => 90,
        ])
        ->assertRedirect(route('crm.applications.forms.index'));

    $this->assertDatabaseHas('application_form_drafts', [
        'id' => $draft->id,
        'application_fee_status' => 'paid',
        'status' => ApplicationFormDraftStatus::SUBMITTED->value,
    ]);
});

it('supports AP-005 multi-programme submission from CRM web flow', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationWebUser('APWDF05');
    $programmeUuids = makeWebProgrammes($institution->id, 3);
    $template = makeSaveResumeTemplate(
        institutionId: $institution->id,
        createdBy: $user->id,
        allowMultiProgrammeApplications: true,
        maxProgrammesPerApplication: 3,
    );

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'web-resume-token-ap005',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'digital_signature',
        'progress_percentage' => 84,
        'selected_programme_uuids' => [$programmeUuids[0], $programmeUuids[1]],
        'application_fee_status' => 'not_required',
        'form_data' => [
            'digital_signature' => [
                'applicant_signature' => 'signed',
            ],
        ],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(7),
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('crm.applications.drafts.submit', $draft->uuid), [
            'progress_percentage' => 90,
            'programme_uuids' => [$programmeUuids[0], $programmeUuids[2]],
        ])
        ->assertRedirect(route('crm.applications.forms.index'));

    $this->assertDatabaseHas('application_form_drafts', [
        'id' => $draft->id,
        'status' => ApplicationFormDraftStatus::SUBMITTED->value,
    ]);
});

it('blocks web fill for non-mobile-optimised template in AP-006', function (): void {
    [$institution, $user] = makeInstitutionAndApplicationWebUser('APWDF06');
    $template = makeSaveResumeTemplate(
        institutionId: $institution->id,
        createdBy: $user->id,
        mobileOptimised: false,
    );

    $this->actingAs($user)
        ->get(route('crm.applications.forms.fill', $template->uuid))
        ->assertRedirect(route('crm.applications.forms.index'));
});
