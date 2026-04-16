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

function makeInstitutionAndApplicationAdmin(string $code): array
{
    $institution = Institution::create([
        'name' => 'Application API Test '.$code,
        'code' => $code,
        'is_active' => true,
    ]);

    $admin = User::create([
        'name' => 'Application API Admin '.$code,
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

function applicationTemplatePayload(): array
{
    return [
        'name' => 'AP-002 UG Template',
        'slug' => 'ap-002-ug-template',
        'minimum_completeness_percentage' => 80,
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
                    ['id' => 'qualification', 'type' => 'text', 'label' => 'Highest Qualification', 'required' => true],
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
        'settings' => [
            'allow_save_and_resume' => true,
            'mobile_optimised' => true,
            'show_progress_bar' => true,
        ],
    ];
}

it('creates application form template via api', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APAPI01');

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates', applicationTemplatePayload());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'AP-002 UG Template')
        ->assertJsonPath('data.slug', 'ap-002-ug-template');

    $this->assertDatabaseHas('application_form_templates', [
        'institution_id' => $institution->id,
        'slug' => 'ap-002-ug-template',
        'minimum_completeness_percentage' => 80,
    ]);
});

it('returns paginated envelope for index endpoint', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APAPI02');

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates', applicationTemplatePayload())
        ->assertCreated();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/application-form-templates?per_page=10');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'data',
            'message',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.per_page', 10);
});

it('enforces tenant isolation for show endpoint', function (): void {
    [$institutionA, $adminA] = makeInstitutionAndApplicationAdmin('APAPI03');
    [, $adminB] = makeInstitutionAndApplicationAdmin('APAPI04');

    $template = ApplicationFormTemplate::withoutGlobalScopes()->create([
        'institution_id' => $institutionA->id,
        'name' => 'Restricted Template',
        'slug' => 'restricted-template',
        'sections' => [
            ['id' => 's1', 'title' => 'Section 1', 'order' => 1, 'fields' => [['id' => 'f1', 'type' => 'text', 'label' => 'Field 1', 'required' => true]]],
        ],
        'minimum_completeness_percentage' => 100,
        'is_active' => true,
        'created_by' => $adminA->id,
    ]);

    $this->actingAs($adminB, 'sanctum')
        ->getJson('/api/v1/crm/application-form-templates/'.$template->uuid)
        ->assertNotFound();
});

it('updates application form template via api', function (): void {
    [$institution, $admin] = makeInstitutionAndApplicationAdmin('APAPI05');

    $created = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates', applicationTemplatePayload())
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/crm/application-form-templates/'.$uuid, [
            'name' => 'AP-001 UG Template Updated',
            'minimum_completeness_percentage' => 90,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'AP-001 UG Template Updated')
        ->assertJsonPath('data.minimum_completeness_percentage', 90);

    $this->assertDatabaseHas('application_form_templates', [
        'institution_id' => $institution->id,
        'slug' => 'ap-002-ug-template',
        'name' => 'AP-001 UG Template Updated',
    ]);
});

it('rejects payload missing required AP-002 sections', function (): void {
    [, $admin] = makeInstitutionAndApplicationAdmin('APAPI06');

    $payload = applicationTemplatePayload();
    $payload['sections'] = array_values(array_filter(
        $payload['sections'],
        static fn (array $section): bool => $section['id'] !== 'co_curricular_activities'
    ));

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sections']);
});

it('rejects payload without digital signature field', function (): void {
    [, $admin] = makeInstitutionAndApplicationAdmin('APAPI07');

    $payload = applicationTemplatePayload();
    $payload['sections'] = array_map(static function (array $section): array {
        if ($section['id'] === 'digital_signature') {
            $section['fields'] = [
                ['id' => 'signature_text', 'type' => 'text', 'label' => 'Signature Text', 'required' => true],
            ];
        }

        return $section;
    }, $payload['sections']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sections']);
});

it('rejects template payload when AP-006 mobile optimisation is disabled', function (): void {
    [, $admin] = makeInstitutionAndApplicationAdmin('APAPI08');

    $payload = applicationTemplatePayload();
    $payload['settings']['mobile_optimised'] = false;

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/application-form-templates', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['settings.mobile_optimised']);
});
