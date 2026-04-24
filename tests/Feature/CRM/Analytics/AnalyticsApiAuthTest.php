<?php

declare(strict_types=1);

// BRD: CRM-AR-021 — Authentication and authorisation tests for Analytics API

use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'api_token.manage', 'guard_name' => 'web']);

    $this->institution  = Institution::factory()->create();
    $this->institutionB = Institution::factory()->create();

    $this->admin = User::factory()->create([
        'institution_id' => $this->institution->id,
    ]);
    $this->admin->givePermissionTo('api_token.manage');
});

describe('No / invalid token', function () {
    it('returns 401 when no Bearer token is provided', function () {
        $this->getJson('/api/v1/crm/analytics/leads')->assertUnauthorized();
    });

    it('returns 401 when Bearer token is invalid', function () {
        $this->withHeaders(['Authorization' => 'Bearer invalid-token-here'])
            ->getJson('/api/v1/crm/analytics/leads')
            ->assertUnauthorized();
    });
});

describe('Token ability gate', function () {
    it('returns 403 when token lacks analytics:read ability', function () {
        $tokenResult = $this->admin->createToken('no-ability-token', []);
        DB::table('personal_access_tokens')
            ->where('id', $tokenResult->accessToken->id)
            ->update(['institution_id' => $this->institution->id]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenResult->plainTextToken])
            ->getJson('/api/v1/crm/analytics/leads')
            ->assertForbidden();
    });

    it('allows access with analytics:read ability', function () {
        $tokenResult = $this->admin->createToken('bi-token', ['analytics:read']);
        DB::table('personal_access_tokens')
            ->where('id', $tokenResult->accessToken->id)
            ->update(['institution_id' => $this->institution->id]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenResult->plainTextToken])
            ->getJson('/api/v1/crm/analytics/leads')
            ->assertOk();
    });
});

describe('Institution isolation', function () {
    it('returns 403 when token institution_id does not match user institution', function () {
        // Token bound to institution B but user belongs to institution A
        $userA = User::factory()->create(['institution_id' => $this->institution->id]);
        $tokenResult = $userA->createToken('mismatched', ['analytics:read']);
        // Manually set institution_id to institution B — simulates a tampered token
        DB::table('personal_access_tokens')
            ->where('id', $tokenResult->accessToken->id)
            ->update(['institution_id' => $this->institutionB->id]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenResult->plainTextToken])
            ->getJson('/api/v1/crm/analytics/leads')
            ->assertForbidden();
    });
});

describe('All four analytics endpoints require auth', function () {
    it('protects /pipeline without token', function () {
        $this->getJson('/api/v1/crm/analytics/pipeline')->assertUnauthorized();
    });

    it('protects /fees without token', function () {
        $this->getJson('/api/v1/crm/analytics/fees')->assertUnauthorized();
    });

    it('protects /counsellors without token', function () {
        $this->getJson('/api/v1/crm/analytics/counsellors')->assertUnauthorized();
    });
});
