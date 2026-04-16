<?php

declare(strict_types=1);

use App\Models\CRM\ApplicationFormTemplate;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeInstitutionAndAppAdmin(string $code): array
{
    $institution = Institution::create([
        'name' => 'Application Test '.$code,
        'code' => $code,
        'is_active' => true,
    ]);

    $admin = User::create([
        'name' => 'Application Admin '.$code,
        'email' => strtolower($code).'@example.test',
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

function ap001TemplatePayload(): array
{
    return [
        'name' => 'UG Admission 2026 AP-002',
        'slug' => 'ug-admission-2026-ap-002',
        'description' => 'Multi-step application template for UG applicants.',
        'minimum_completeness_percentage' => 85,
        'is_active' => true,
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
                'id' => 'academic_history',
                'title' => 'Academic History',
                'order' => 2,
                'fields' => [
                    ['id' => 'class_12_percentage', 'type' => 'number', 'label' => 'Class 12 Percentage', 'required' => true],
                ],
            ],
            [
                'id' => 'entrance_exam_scores',
                'title' => 'Entrance Exam Scores',
                'order' => 3,
                'fields' => [
                    ['id' => 'exam_score', 'type' => 'number', 'label' => 'Exam Score', 'required' => false],
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
                    ['id' => 'applicant_signature', 'type' => 'signature', 'label' => 'Applicant Signature', 'required' => true],
                ],
            ],
        ],
        'progression_rules' => [
            [
                'from_section' => 'personal_details',
                'to_section' => 'academic_history',
                'condition_field' => 'email',
                'condition_operator' => 'contains',
                'condition_value' => '@',
            ],
        ],
    ];
}

it('creates an application form template from web route', function (): void {
    [$institution, $admin] = makeInstitutionAndAppAdmin('APW01');

    $response = $this->actingAs($admin)
        ->post(route('crm.applications.forms.store'), ap001TemplatePayload());

    $response->assertRedirect(route('crm.applications.forms.index'));

    $this->assertDatabaseHas('application_form_templates', [
        'institution_id' => $institution->id,
        'name' => 'UG Admission 2026 AP-002',
        'slug' => 'ug-admission-2026-ap-002',
        'minimum_completeness_percentage' => 85,
        'is_active' => true,
    ]);
});

it('does not allow another institution user to edit template', function (): void {
    [$institutionA, $adminA] = makeInstitutionAndAppAdmin('APW02');
    [, $adminB] = makeInstitutionAndAppAdmin('APW03');

    $template = ApplicationFormTemplate::withoutGlobalScopes()->create([
        'institution_id' => $institutionA->id,
        'name' => 'PG Application',
        'slug' => 'pg-application',
        'sections' => [
            ['id' => 'basic', 'title' => 'Basic', 'order' => 1, 'fields' => [['id' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true]]],
        ],
        'minimum_completeness_percentage' => 100,
        'is_active' => true,
        'created_by' => $adminA->id,
    ]);

    $this->actingAs($adminB)
        ->get(route('crm.applications.forms.edit', $template->uuid))
        ->assertNotFound();
});

it('rejects web payload that omits required AP-002 sections', function (): void {
    [, $admin] = makeInstitutionAndAppAdmin('APW04');

    $payload = ap001TemplatePayload();
    $payload['sections'] = array_values(array_filter(
        $payload['sections'],
        static fn (array $section): bool => $section['id'] !== 'digital_signature'
    ));

    $response = $this->actingAs($admin)
        ->from(route('crm.applications.forms.create'))
        ->post(route('crm.applications.forms.store'), $payload);

    $response->assertRedirect(route('crm.applications.forms.create'));
    $response->assertSessionHasErrors(['sections']);
});

it('shows AP-002 readiness preview on form builder screen', function (): void {
    [, $admin] = makeInstitutionAndAppAdmin('APW05');

    $this->actingAs($admin)
        ->get(route('crm.applications.forms.create'))
        ->assertOk()
        ->assertSeeText('AP-002 Readiness Preview')
        ->assertSeeText('digital_signature');
});
