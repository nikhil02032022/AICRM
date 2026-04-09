<?php

declare(strict_types=1);

// BRD: CRM-LC-001 — Web form API CRUD tests
// BRD: CRM-LC-009 — QR code endpoint test
use App\Enums\CRM\LeadSource;
use App\Models\CRM\Institution;
use App\Models\CRM\WebForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\PermissionSeeder::class);
});

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeInstitutionAndAdmin(): array
{
    $institution = Institution::create([
        'name' => 'Test University', 'code' => 'WF01', 'is_active' => true,
    ]);

    $admin = User::create([
        'name'           => 'Form Admin',
        'email'          => 'formadmin@tu.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $admin->givePermissionTo(['crm.forms.view', 'crm.forms.create', 'crm.forms.edit', 'crm.forms.delete']);

    return [$institution, $admin];
}

function minimalFormPayload(): array
{
    return [
        'name'                 => 'MBA 2026 Walk-in',
        'slug'                 => 'mba-2026-walk-in',
        'fields'               => [
            ['id' => 'programme', 'type' => 'select', 'label' => 'Programme', 'required' => true, 'options' => ['MBA', 'MCA'], 'show_if' => null],
        ],
        'source'               => LeadSource::EVENT->value,
        'is_active'            => true,
        'consent_form_version' => 'v1.0',
    ];
}

// ─── Tests ──────────────────────────────────────────────────────────────────

// BRD: CRM-LC-001 — Can create a web form via API
it('can create a web form via API', function (): void {
    [$institution, $admin] = makeInstitutionAndAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/forms', minimalFormPayload());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'MBA 2026 Walk-in')
        ->assertJsonPath('data.slug', 'mba-2026-walk-in');

    $this->assertDatabaseHas('web_forms', [
        'institution_id' => $institution->id,
        'slug'           => 'mba-2026-walk-in',
    ]);
});

// BRD: CRM-LC-001 — Slug is unique per institution (different institutions can share)
it('allows same slug in different institutions', function (): void {
    $inst1 = Institution::create(['name' => 'Uni A', 'code' => 'UNA', 'is_active' => true]);
    $inst2 = Institution::create(['name' => 'Uni B', 'code' => 'UNB', 'is_active' => true]);

    $adminA = User::create(['name' => 'A', 'email' => 'a@uni.com', 'password' => bcrypt('p'), 'institution_id' => $inst1->id]);
    $adminA->givePermissionTo(['crm.forms.create', 'crm.forms.view']);

    $adminB = User::create(['name' => 'B', 'email' => 'b@uni.com', 'password' => bcrypt('p'), 'institution_id' => $inst2->id]);
    $adminB->givePermissionTo(['crm.forms.create', 'crm.forms.view']);

    $payload = minimalFormPayload();

    $this->actingAs($adminA, 'sanctum')->postJson('/api/v1/crm/forms', $payload)->assertCreated();
    $this->actingAs($adminB, 'sanctum')->postJson('/api/v1/crm/forms', $payload)->assertCreated();

    expect(WebForm::withoutGlobalScopes()->where('slug', 'mba-2026-walk-in')->count())->toBe(2);
});

// BRD: CRM-LC-001 — Cannot access another institution's form
it('cannot read a form belonging to another institution', function (): void {
    [$instA, $adminA] = makeInstitutionAndAdmin();

    // Create form under instA, then try to access with user from different institution
    $instB = Institution::create(['name' => 'Uni B2', 'code' => 'UNB2', 'is_active' => true]);
    $adminB = User::create(['name' => 'UserB', 'email' => 'userb@b.com', 'password' => bcrypt('p'), 'institution_id' => $instB->id]);
    $adminB->givePermissionTo(['crm.forms.view']);

    $form = WebForm::withoutGlobalScopes()->create([
        'uuid'                 => (string) Str::uuid(),
        'institution_id'       => $instA->id,
        'name'                 => 'Secret Form',
        'slug'                 => 'secret-form',
        'fields'               => json_encode([]),
        'embed_token'          => Str::random(64),
        'source'               => 'website_organic',
        'consent_form_version' => 'v1.0',
        'is_active'            => true,
    ]);

    // adminB should get 404 (InstitutionScope filters it out)
    $this->actingAs($adminB, 'sanctum')
        ->getJson('/api/v1/crm/forms/' . $form->uuid)
        ->assertNotFound();
});

// BRD: CRM-LC-001 — Inactive form returns 404 on public URL
it('inactive form returns 404 on public URL', function (): void {
    $institution = Institution::create(['name' => 'Test U', 'code' => 'TU99', 'is_active' => true]);

    WebForm::withoutGlobalScopes()->create([
        'uuid'                 => (string) Str::uuid(),
        'institution_id'       => $institution->id,
        'name'                 => 'Inactive Form',
        'slug'                 => 'inactive-form',
        'fields'               => json_encode([]),
        'embed_token'          => Str::random(64),
        'source'               => 'website_organic',
        'consent_form_version' => 'v1.0',
        'is_active'            => false,   // explicitly inactive
    ]);

    $this->get('/f/inactive-form')->assertNotFound();
});

// BRD: CRM-LC-001 — Can list forms (returns paginated resource)
it('returns paginated list of own institution forms', function (): void {
    [$institution, $admin] = makeInstitutionAndAdmin();

    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/crm/forms', minimalFormPayload())->assertCreated();

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/crm/forms');

    $response->assertOk()->assertJsonCount(1, 'data');
});

// BRD: CRM-LC-001 — Can update a form
it('can update a web form', function (): void {
    [$institution, $admin] = makeInstitutionAndAdmin();

    $createRes = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/forms', minimalFormPayload())
        ->assertCreated();

    $uuid = $createRes->json('data.uuid');

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/crm/forms/' . $uuid, ['name' => 'Updated Form Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Form Name');
});

// BRD: CRM-LC-001 — Can soft delete a form
it('can soft delete a form', function (): void {
    [$institution, $admin] = makeInstitutionAndAdmin();

    $createRes = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/forms', minimalFormPayload())
        ->assertCreated();

    $uuid = $createRes->json('data.uuid');

    $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/crm/forms/' . $uuid)
        ->assertOk();

    $this->assertSoftDeleted('web_forms', ['uuid' => $uuid]);
});

// BRD: CRM-LC-001 — embed_token not exposed in API responses
it('does not expose embed_token in API response', function (): void {
    [$institution, $admin] = makeInstitutionAndAdmin();

    $res = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/forms', minimalFormPayload())
        ->assertCreated();

    expect($res->json('data'))->not->toHaveKey('embed_token');
    expect($res->json('data'))->not->toHaveKey('institution_id');
});
