<?php

declare(strict_types=1);

use App\Domain\CRM\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('api health check returns standard success envelope', function (): void {
    $institution = Institution::create([
        'name' => 'Test University', 'code' => 'TU01', 'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Test User', 'email' => 'user@tu.com',
        'password' => bcrypt('password'), 'institution_id' => $institution->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/crm/health-check');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data', 'message'])
        ->assertJsonPath('success', true);
});

test('unauthenticated api request returns 401 with standard error envelope', function (): void {
    $response = $this->getJson('/api/v1/crm/health-check');

    $response->assertStatus(401)
        ->assertJsonStructure(['success', 'error' => ['code', 'message']])
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'UNAUTHENTICATED');
});

test('api request without institution returns 403 tenancy error', function (): void {
    $user = User::create([
        'name' => 'No Inst User', 'email' => 'noinst@test.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/crm/health-check');

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});
