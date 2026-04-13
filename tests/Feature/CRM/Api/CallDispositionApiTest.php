<?php

declare(strict_types=1);

use App\Models\CRM\CallDispositionConfig;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);
});

function makeDispositionApiContext(): array
{
    $institution = Institution::create([
        'name' => 'Disposition API Institute',
        'code' => 'DAI2',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Disposition API User',
        'email' => 'disposition-api@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.communication.send', 'crm.settings.manage']);

    return [$institution, $user];
}

it('lists disposition configs via api', function (): void {
    /** @var \Tests\TestCase $this */
    [, $user] = makeDispositionApiContext();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/voice/call-dispositions')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Call dispositions fetched successfully.');
});

it('updates disposition config via api', function (): void {
    /** @var \Tests\TestCase $this */
    [$institution, $user] = makeDispositionApiContext();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/voice/call-dispositions')
        ->assertOk();

    $config = CallDispositionConfig::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->where('code', 'INTERESTED')
        ->firstOrFail();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/crm/voice/call-dispositions/'.$config->uuid, [
            'label' => 'Highly Interested',
            'is_active' => true,
            'requires_follow_up' => true,
            'sort_order' => 1,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.label', 'Highly Interested')
        ->assertJsonPath('data.requires_follow_up', true);
});
