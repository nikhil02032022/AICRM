<?php

declare(strict_types=1);

use App\Models\CRM\AiUsageLog;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('returns ai usage logs for audit api endpoint', function (): void {
    $institution = Institution::create([
        'name' => 'AI Usage API Institute',
        'code' => 'AUI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Audit User',
        'email' => 'audit-user@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.view', 'crm.leads.edit']);

    AiUsageLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'feature_key' => 'next_best_action',
        'action' => 'recommended',
        'event_name' => 'App\\Events\\CRM\\LeadNbaRecommendedEvent',
        'reference_uuid' => (string) Str::uuid(),
        'context' => ['confidence_score' => 76],
        'occurred_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/scoring/ai-usage-logs?feature_key=next_best_action');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.feature_key', 'next_best_action')
        ->assertJsonPath('data.0.action', 'recommended');
});
