<?php

declare(strict_types=1);

use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\QualificationQuestionnaire;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeInstitutionAndQuestionnaireManager(): array
{
    $institution = Institution::create([
        'name' => 'Questionnaire University',
        'code' => 'QU01',
        'is_active' => true,
    ]);

    $manager = User::create([
        'name' => 'Questionnaire Manager',
        'email' => 'qm@qu.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $manager->givePermissionTo(['crm.questionnaires.manage', 'crm.questionnaires.respond', 'crm.leads.create', 'crm.leads.view']);

    return [$institution, $manager];
}

function questionnairePayload(): array
{
    return [
        'name' => 'BANT 2026',
        'status' => 'active',
        'questions' => [
            ['key' => 'budget', 'label' => 'Budget Range', 'type' => 'select', 'required' => true, 'options' => ['lt_2_lakh', '2_to_5_lakh', 'gt_5_lakh']],
            ['key' => 'timeline', 'label' => 'Admission Timeline', 'type' => 'text', 'required' => true],
        ],
    ];
}

it('creates qualification questionnaire via api', function (): void {
    [, $manager] = makeInstitutionAndQuestionnaireManager();

    $response = $this->actingAs($manager, 'sanctum')
        ->postJson('/api/v1/crm/scoring/questionnaires', questionnairePayload());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'BANT 2026')
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('qualification_questionnaires', ['name' => 'BANT 2026']);
});

it('lists only institution questionnaires', function (): void {
    [$institutionA, $managerA] = makeInstitutionAndQuestionnaireManager();
    $institutionB = Institution::create(['name' => 'QB', 'code' => 'QB01', 'is_active' => true]);

    QualificationQuestionnaire::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institutionA->id,
        'name' => 'Inst A Questionnaire',
        'status' => 'active',
        'questions' => [['key' => 'need', 'label' => 'Need', 'type' => 'text']],
    ]);

    QualificationQuestionnaire::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institutionB->id,
        'name' => 'Inst B Questionnaire',
        'status' => 'active',
        'questions' => [['key' => 'budget', 'label' => 'Budget', 'type' => 'text']],
    ]);

    $response = $this->actingAs($managerA, 'sanctum')
        ->getJson('/api/v1/crm/scoring/questionnaires');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Inst A Questionnaire');
});

it('stores questionnaire response for a lead', function (): void {
    [$institution, $manager] = makeInstitutionAndQuestionnaireManager();

    $questionnaire = QualificationQuestionnaire::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'BANT Form',
        'status' => 'active',
        'questions' => [['key' => 'awareness', 'label' => 'Awareness', 'type' => 'text']],
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Ravi',
        'last_name' => 'Kumar',
        'mobile' => '9876543210',
        'source' => LeadSource::WALK_IN->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'temperature' => LeadTemperature::COLD->value,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    $response = $this->actingAs($manager, 'sanctum')
        ->putJson('/api/v1/crm/scoring/questionnaires/'.$questionnaire->uuid.'/responses/'.$lead->uuid, [
            'responses' => [
                'awareness' => 'Visited admission webinar and brochure page twice',
            ],
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.lead_uuid', $lead->uuid);

    $this->assertDatabaseHas('questionnaire_responses', [
        'qualification_questionnaire_id' => $questionnaire->id,
        'lead_id' => $lead->id,
    ]);
});

it('updates questionnaire via api', function (): void {
    [$institution, $manager] = makeInstitutionAndQuestionnaireManager();

    $questionnaire = QualificationQuestionnaire::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'Old Name',
        'status' => 'draft',
        'questions' => [['key' => 'need', 'label' => 'Need', 'type' => 'text']],
    ]);

    $response = $this->actingAs($manager, 'sanctum')
        ->putJson('/api/v1/crm/scoring/questionnaires/'.$questionnaire->uuid, [
            'name' => 'Updated BANT',
            'status' => 'active',
            'questions' => [['key' => 'budget', 'label' => 'Budget', 'type' => 'text', 'required' => true]],
        ]);

    $response->assertOk()->assertJsonPath('data.name', 'Updated BANT');
    $this->assertDatabaseHas('qualification_questionnaires', ['id' => $questionnaire->id, 'name' => 'Updated BANT']);
});

it('archives questionnaire via api', function (): void {
    [$institution, $manager] = makeInstitutionAndQuestionnaireManager();

    $questionnaire = QualificationQuestionnaire::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'Archive Me',
        'status' => 'active',
        'questions' => [['key' => 'need', 'label' => 'Need', 'type' => 'text']],
    ]);

    $this->actingAs($manager, 'sanctum')
        ->deleteJson('/api/v1/crm/scoring/questionnaires/'.$questionnaire->uuid)
        ->assertOk();

    $this->assertSoftDeleted('qualification_questionnaires', ['id' => $questionnaire->id]);
});
