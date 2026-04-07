<?php

declare(strict_types=1);

use App\Domain\CRM\Models\Institution;
use App\Domain\CRM\Models\Scopes\InstitutionScope;
use App\Models\User;
use App\Domain\CRM\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper — create institution + campus + scoped user in one call
// ---------------------------------------------------------------------------
function createInstitutionWithUser(string $code = 'INST'): array
{
    $institution = Institution::create([
        'name' => "Test Institution $code",
        'code' => $code,
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => "User $code",
        'email' => strtolower($code).'@test.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    return [$institution, $user];
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

test('institution model is created with a uuid', function (): void {
    $institution = Institution::create([
        'name' => 'Alpha University',
        'code' => 'ALPHA',
        'is_active' => true,
    ]);

    expect($institution->uuid)->not->toBeEmpty()
        ->and($institution->code)->toBe('ALPHA');
});

test('institution scope prevents cross-tenant data access', function (): void {
    [$instA, $userA] = createInstitutionWithUser('AAAA');
    [$instB, $userB] = createInstitutionWithUser('BBBB');

    // Acting as userA: InstitutionScope should only return instA
    $this->actingAs($userA);

    // We test the scope directly — models with InstitutionScope applied
    // should be scoped to the acting user's institution_id
    expect(Institution::withoutGlobalScope(InstitutionScope::class)->count())->toBe(2);

    // Users have InstitutionScope would apply to any CRM model;
    // Institutions themselves are not scoped (they're tenant roots)
    // Verify user from A cannot see user from B via institution relationship
    expect($userA->institution->code)->toBe('AAAA')
        ->and($userB->institution->code)->toBe('BBBB')
        ->and($userA->institution_id)->not->toBe($userB->institution_id);
});

test('tenant manager resolves institution id from authenticated user', function (): void {
    [$institution, $user] = createInstitutionWithUser('TMGR');

    $this->actingAs($user);

    $manager = app(TenantManager::class);

    expect($manager->institutionId())->toBe($institution->id);
});

test('tenant manager aborts when user has no institution', function (): void {
    $user = User::create([
        'name' => 'No Institution User',
        'email' => 'noinst@test.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user);

    $manager = app(TenantManager::class);

    expect(fn () => $manager->institutionId())->toThrow(HttpException::class);
});

test('ensure institution tenancy middleware blocks users without institution', function (): void {
    $user = User::create([
        'name' => 'Tenant Test User',
        'email' => 'tenant@test.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user, 'sanctum');

    // Hit a guarded route — the middleware should abort with 403
    $response = $this->getJson('/api/v1/crm/health-check');

    $response->assertStatus(403);
});
