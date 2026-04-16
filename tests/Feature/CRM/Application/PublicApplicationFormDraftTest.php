<?php

declare(strict_types=1);

use App\Enums\CRM\ApplicationFormDraftStatus;
use App\Models\CRM\ApplicationFormDraft;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\ApplicationFormTemplate;
use App\Models\CRM\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makePublicSaveResumeTemplate(
    string $code,
    bool $mobileOptimised = true,
    bool $applicationFeeEnabled = false,
    float $applicationFeeAmount = 0.0,
    bool $allowMultiProgrammeApplications = false,
    int $maxProgrammesPerApplication = 1,
): ApplicationFormTemplate
{
    $institution = Institution::create([
        'name' => 'Public AP-003 Test '.$code,
        'code' => $code,
        'is_active' => true,
    ]);

    return ApplicationFormTemplate::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'name' => 'Public Application Template '.$code,
        'slug' => 'public-application-template-'.strtolower($code),
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
        'minimum_completeness_percentage' => 75,
        'is_active' => true,
    ]);
}

/** @return list<string> */
function makePublicProgrammes(int $institutionId, int $count = 3): array
{
    $uuids = [];

    for ($i = 1; $i <= $count; $i++) {
        $programme = CrmProgramme::withoutGlobalScopes()->create([
            'institution_id' => $institutionId,
            'name' => 'Public Programme '.$i,
            'code' => 'PPRG'.$i,
            'level' => 'UG',
            'department' => 'Admissions',
            'is_active' => true,
            'erp_programme_uuid' => (string) fake()->uuid(),
        ]);

        $uuids[] = (string) $programme->erp_programme_uuid;
    }

    return $uuids;
}

it('shows public application fill page by slug', function (): void {
    $template = makePublicSaveResumeTemplate('PUBAP01');

    $this->get('/apply/'.$template->slug)
        ->assertOk()
        ->assertSeeText($template->name);
});

it('creates public draft and resumes by token', function (): void {
    $template = makePublicSaveResumeTemplate('PUBAP02');

    $this->post('/apply/'.$template->slug.'/save', [
        'current_section_id' => 'personal_details',
        'last_completed_section_order' => 1,
        'progress_percentage' => 30,
        'form_data' => [
            'personal_details' => [
                'first_name' => 'Riya',
            ],
        ],
    ])->assertRedirect();

    $draft = ApplicationFormDraft::withoutGlobalScopes()->where('application_form_template_id', $template->id)->first();

    expect($draft)->not->toBeNull();

    $this->get('/apply/resume/'.$draft->resume_token)
        ->assertOk()
        ->assertSeeText($template->name);
});

it('submits public draft from resume flow', function (): void {
    $template = makePublicSaveResumeTemplate('PUBAP03');

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $template->institution_id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'public-resume-token-1',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'digital_signature',
        'progress_percentage' => 78,
        'form_data' => [
            'digital_signature' => [
                'applicant_signature' => 'signed',
            ],
        ],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(5),
    ]);

    $this->post('/apply/resume/'.$draft->resume_token.'/submit', [
        'progress_percentage' => 80,
    ])->assertRedirect('/apply/resume/'.$draft->resume_token);

    $this->assertDatabaseHas('application_form_drafts', [
        'id' => $draft->id,
        'status' => ApplicationFormDraftStatus::SUBMITTED->value,
    ]);
});

it('requires public fee payment before AP-004 submission', function (): void {
    $template = makePublicSaveResumeTemplate(
        code: 'PUBAP04',
        mobileOptimised: true,
        applicationFeeEnabled: true,
        applicationFeeAmount: 900.00,
    );

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $template->institution_id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'public-resume-token-fee-1',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'digital_signature',
        'progress_percentage' => 78,
        'application_fee_amount' => 900,
        'application_fee_currency' => 'INR',
        'application_fee_status' => 'pending',
        'form_data' => [
            'digital_signature' => [
                'applicant_signature' => 'signed',
            ],
        ],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(5),
    ]);

    $this->post('/apply/resume/'.$draft->resume_token.'/submit', [
        'progress_percentage' => 80,
    ])->assertSessionHasErrors(['application_fee_status']);

    $this->post('/apply/resume/'.$draft->resume_token.'/pay-fee', [
        'gateway' => 'online',
        'transaction_reference' => 'PUBLIC-FEE-1001',
    ])->assertRedirect('/apply/resume/'.$draft->resume_token);

    $this->post('/apply/resume/'.$draft->resume_token.'/submit', [
        'progress_percentage' => 80,
    ])->assertRedirect('/apply/resume/'.$draft->resume_token);

    $this->assertDatabaseHas('application_form_drafts', [
        'id' => $draft->id,
        'application_fee_status' => 'paid',
        'status' => ApplicationFormDraftStatus::SUBMITTED->value,
    ]);
});

it('supports AP-005 multi-programme submission from public flow', function (): void {
    $template = makePublicSaveResumeTemplate(
        code: 'PUBAP05',
        allowMultiProgrammeApplications: true,
        maxProgrammesPerApplication: 3,
    );
    $programmeUuids = makePublicProgrammes((int) $template->institution_id, 3);

    $draft = ApplicationFormDraft::withoutGlobalScopes()->create([
        'institution_id' => $template->institution_id,
        'application_form_template_id' => $template->id,
        'resume_token' => 'public-resume-token-ap005',
        'status' => ApplicationFormDraftStatus::DRAFT,
        'current_section_id' => 'digital_signature',
        'progress_percentage' => 79,
        'selected_programme_uuids' => [$programmeUuids[0], $programmeUuids[1]],
        'application_fee_status' => 'not_required',
        'form_data' => [
            'digital_signature' => [
                'applicant_signature' => 'signed',
            ],
        ],
        'last_saved_at' => now(),
        'expires_at' => now()->addDays(5),
    ]);

    $this->post('/apply/resume/'.$draft->resume_token.'/submit', [
        'progress_percentage' => 80,
        'programme_uuids' => [$programmeUuids[0], $programmeUuids[2]],
    ])->assertRedirect('/apply/resume/'.$draft->resume_token);

    $this->assertDatabaseHas('application_form_drafts', [
        'id' => $draft->id,
        'status' => ApplicationFormDraftStatus::SUBMITTED->value,
    ]);
});

it('does not expose non-mobile-optimised template on public route in AP-006', function (): void {
    $template = makePublicSaveResumeTemplate('PUBAP06', false);

    $this->get('/apply/'.$template->slug)
        ->assertNotFound();
});
