<?php

declare(strict_types=1);

// BRD: CRM-EC-005 — Custom field management API tests
// Covers: CRUD, field_key immutability, RBAC, institution isolation, options for select type

use App\Enums\CRM\CustomFieldEntity;
use App\Enums\CRM\CustomFieldType;
use App\Models\CRM\CustomField;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seed(PermissionSeeder::class);
});

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeCustomFieldAdmin(string $suffix = 'a'): array
{
    $institution = Institution::create([
        'name' => 'CF Institute ' . $suffix,
        'code' => 'CF' . strtoupper($suffix),
        'is_active' => true,
    ]);

    $admin = User::create([
        'name' => 'CF Admin ' . $suffix,
        'email' => 'cf-admin-' . $suffix . '@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $admin->givePermissionTo([
        'crm.settings.custom-fields.view',
        'crm.settings.custom-fields.manage',
    ]);

    return [$institution, $admin];
}

function customFieldPayload(array $overrides = []): array
{
    return array_merge([
        'entity' => 'lead',
        'label'  => 'Previous Institution',
        'type'   => 'text',
        'is_required' => false,
    ], $overrides);
}

// ─── CRM-EC-005 CREATE ───────────────────────────────────────────────────────

it('admin can create a custom field (CRM-EC-005)', function (): void {
    [$institution, $admin] = makeCustomFieldAdmin();

    $response = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', customFieldPayload())
        ->assertCreated();

    $response->assertJsonPath('data.label', 'Previous Institution')
        ->assertJsonPath('data.entity', 'lead')
        ->assertJsonPath('data.type', 'text');

    assertDatabaseHas('custom_fields', [
        'label'          => 'Previous Institution',
        'entity'         => 'lead',
        'institution_id' => $institution->id,
    ]);
});

it('auto-derives field_key from label when not supplied (CRM-EC-005)', function (): void {
    [, $admin] = makeCustomFieldAdmin('b');

    $response = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', customFieldPayload(['label' => 'Guardian Contact Number']))
        ->assertCreated();

    $response->assertJsonPath('data.field_key', 'guardian_contact_number');
});

it('stores options array for select type fields (CRM-EC-005)', function (): void {
    [, $admin] = makeCustomFieldAdmin('c');

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', customFieldPayload([
            'type'    => 'select',
            'label'   => 'Preferred Intake',
            'options' => [
                ['value' => 'jan', 'label' => 'January'],
                ['value' => 'jul', 'label' => 'July'],
            ],
        ]))
        ->assertCreated()
        ->assertJsonCount(2, 'data.options');
});

it('validates required fields on create (CRM-EC-005)', function (): void {
    [, $admin] = makeCustomFieldAdmin('d');

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entity', 'label', 'type']);
});

// ─── RBAC ──────────────────────────────────────────────────────────────────

it('user without manage permission cannot create custom field (CRM-EC-005)', function (): void {
    [$institution] = makeCustomFieldAdmin();

    $viewer = User::create([
        'name'           => 'Viewer',
        'email'          => 'viewer-cf@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $viewer->givePermissionTo('crm.settings.custom-fields.view');

    actingAs($viewer, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', customFieldPayload())
        ->assertForbidden();
});

it('unauthenticated request is rejected (CRM-EC-005)', function (): void {
    $this->postJson('/api/v1/crm/custom-fields', customFieldPayload())
        ->assertUnauthorized();
});

// ─── READ ──────────────────────────────────────────────────────────────────

it('lists only institution-scoped custom fields (CRM-EC-005)', function (): void {
    [$instA, $adminA] = makeCustomFieldAdmin('e');
    [$instB, $adminB] = makeCustomFieldAdmin('f');

    actingAs($adminA, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', customFieldPayload(['label' => 'Field A']))
        ->assertCreated();

    actingAs($adminB, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', customFieldPayload(['label' => 'Field B']))
        ->assertCreated();

    actingAs($adminA, 'sanctum')
        ->getJson('/api/v1/crm/custom-fields?entity=lead')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.label', 'Field A');
});

it('cannot view custom fields from another institution (CRM-EC-005)', function (): void {
    [$instA, $adminA] = makeCustomFieldAdmin('g');
    [$instB, $adminB] = makeCustomFieldAdmin('h');

    $field = CustomField::withoutGlobalScopes()->create([
        'institution_id' => $instA->id,
        'entity'         => CustomFieldEntity::LEAD,
        'field_key'      => 'other_field',
        'label'          => 'Other Field',
        'type'           => CustomFieldType::TEXT,
        'is_required'    => false,
        'is_active'      => true,
        'sort_order'     => 0,
    ]);

    actingAs($adminB, 'sanctum')
        ->getJson('/api/v1/crm/custom-fields/' . $field->uuid)
        ->assertNotFound();
});

// ─── UPDATE ────────────────────────────────────────────────────────────────

it('can update label and required flag (CRM-EC-005)', function (): void {
    [$institution, $admin] = makeCustomFieldAdmin('i');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', customFieldPayload(['label' => 'Old Label']))
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->putJson('/api/v1/crm/custom-fields/' . $uuid, ['label' => 'New Label', 'is_required' => true])
        ->assertOk()
        ->assertJsonPath('data.label', 'New Label')
        ->assertJsonPath('data.is_required', true);
});

it('field_key is immutable — cannot be changed after creation (CRM-EC-005)', function (): void {
    [, $admin] = makeCustomFieldAdmin('j');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', customFieldPayload(['label' => 'Test Label']))
        ->assertCreated();

    $uuid     = $created->json('data.uuid');
    $original = $created->json('data.field_key');

    actingAs($admin, 'sanctum')
        ->putJson('/api/v1/crm/custom-fields/' . $uuid, ['field_key' => 'hacked_key'])
        ->assertOk()
        ->assertJsonPath('data.field_key', $original); // key unchanged
});

// ─── DELETE ────────────────────────────────────────────────────────────────

it('can soft-delete a custom field (CRM-EC-005)', function (): void {
    [, $admin] = makeCustomFieldAdmin('k');

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', customFieldPayload())
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/crm/custom-fields/' . $uuid)
        ->assertOk()
        ->assertJsonPath('success', true);

    assertSoftDeleted('custom_fields', ['uuid' => $uuid]);
});

// ─── AUDIT LOG ─────────────────────────────────────────────────────────────

it('audit log entry is written on custom field creation (CRM-EC-005)', function (): void {
    [, $admin] = makeCustomFieldAdmin('l');

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/custom-fields', customFieldPayload(['label' => 'Audit Field']))
        ->assertCreated();

    assertDatabaseHas('audit_logs', [
        'entity_type' => \App\Models\CRM\CustomField::class,
        'action'      => 'created',
    ]);
});
