<?php

declare(strict_types=1);

// BRD: CRM-AR-021 — Token management UI: issue, list, revoke; role-based access control

use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'api_token.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'crm.admin.access',  'guard_name' => 'web']);

    $adminRole = Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);
    $adminRole->givePermissionTo(['api_token.manage', 'crm.admin.access']);

    $counsellorRole = Role::firstOrCreate(['name' => 'counsellor', 'guard_name' => 'web']);

    $this->institution  = Institution::factory()->create();
    $this->institutionB = Institution::factory()->create();

    $this->admin = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->admin->assignRole('institution-admin');

    $this->counsellor = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->counsellor->assignRole('counsellor');
});

describe('GET /admin/api-tokens', function () {
    it('admin can view the token management index', function () {
        $this->actingAs($this->admin)
            ->get(route('crm.admin.api-tokens.index'))
            ->assertOk()
            ->assertSee('API Token Management');
    });

    it('counsellor cannot access token management', function () {
        $this->actingAs($this->counsellor)
            ->get(route('crm.admin.api-tokens.index'))
            ->assertForbidden();
    });

    it('unauthenticated user is redirected to login', function () {
        $this->get(route('crm.admin.api-tokens.index'))
            ->assertRedirect();
    });
});

describe('POST /admin/api-tokens (issue)', function () {
    it('admin can issue a named token', function () {
        $this->actingAs($this->admin)
            ->post(route('crm.admin.api-tokens.store'), ['name' => 'PowerBI Test'])
            ->assertRedirect(route('crm.admin.api-tokens.index'));

        $this->assertDatabaseHas('personal_access_tokens', [
            'name'           => 'PowerBI Test',
            'institution_id' => $this->institution->id,
        ]);
    });

    it('plain-text token is flashed to session on issue', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('crm.admin.api-tokens.store'), ['name' => 'Tableau Prod']);

        $response->assertSessionHas('plain_token');
        $plainToken = session('plain_token');
        expect($plainToken)->toBeString()->not->toBeEmpty();
    });

    it('token is bound to admin institution_id', function () {
        $this->actingAs($this->admin)
            ->post(route('crm.admin.api-tokens.store'), ['name' => 'Test Token']);

        $token = PersonalAccessToken::where('name', 'Test Token')->first();
        expect($token->institution_id)->toBe($this->institution->id);
    });

    it('token carries analytics:read ability', function () {
        $this->actingAs($this->admin)
            ->post(route('crm.admin.api-tokens.store'), ['name' => 'Scope Test']);

        $token = PersonalAccessToken::where('name', 'Scope Test')->first();
        $abilities = json_decode($token->abilities, true);
        expect($abilities)->toContain('analytics:read');
    });

    it('name is required', function () {
        $this->actingAs($this->admin)
            ->post(route('crm.admin.api-tokens.store'), ['name' => ''])
            ->assertSessionHasErrors(['name']);
    });

    it('name must not exceed 100 characters', function () {
        $this->actingAs($this->admin)
            ->post(route('crm.admin.api-tokens.store'), ['name' => str_repeat('a', 101)])
            ->assertSessionHasErrors(['name']);
    });

    it('counsellor cannot issue a token', function () {
        $this->actingAs($this->counsellor)
            ->post(route('crm.admin.api-tokens.store'), ['name' => 'Unauthorized'])
            ->assertForbidden();
    });
});

describe('DELETE /admin/api-tokens/{token} (revoke)', function () {
    it('admin can revoke their own institution token', function () {
        $tokenResult = $this->admin->createToken('To Revoke', ['analytics:read']);
        DB::table('personal_access_tokens')
            ->where('id', $tokenResult->accessToken->id)
            ->update(['institution_id' => $this->institution->id]);

        $this->actingAs($this->admin)
            ->delete(route('crm.admin.api-tokens.destroy', $tokenResult->accessToken->id))
            ->assertRedirect(route('crm.admin.api-tokens.index'));

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenResult->accessToken->id]);
    });

    it('admin cannot revoke a token belonging to another institution', function () {
        $adminB = User::factory()->create(['institution_id' => $this->institutionB->id]);
        $tokenResult = $adminB->createToken('Other Inst Token', ['analytics:read']);
        DB::table('personal_access_tokens')
            ->where('id', $tokenResult->accessToken->id)
            ->update(['institution_id' => $this->institutionB->id]);

        $this->actingAs($this->admin)
            ->delete(route('crm.admin.api-tokens.destroy', $tokenResult->accessToken->id))
            ->assertForbidden();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenResult->accessToken->id]);
    });

    it('counsellor cannot revoke any token', function () {
        $tokenResult = $this->admin->createToken('Counsellor Test', ['analytics:read']);
        DB::table('personal_access_tokens')
            ->where('id', $tokenResult->accessToken->id)
            ->update(['institution_id' => $this->institution->id]);

        $this->actingAs($this->counsellor)
            ->delete(route('crm.admin.api-tokens.destroy', $tokenResult->accessToken->id))
            ->assertForbidden();
    });
});
