<?php

declare(strict_types=1);

use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeProgrammeAdmin(string $code): User
{
    $institution = Institution::create([
        'name' => 'Programme Catalogue '.$code,
        'code' => $code,
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Programme Admin '.$code,
        'email' => strtolower($code).'@example.test',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo([
        'crm.applications.view',
        'crm.applications.edit',
    ]);

    return $user;
}

it('shows programme catalogue page', function (): void {
    $user = makeProgrammeAdmin('APPRG01');

    $this->actingAs($user)
        ->get(route('crm.applications.programmes.index'))
        ->assertOk()
        ->assertSeeText('Programme Catalogue');
});

it('creates programme from catalogue setup page', function (): void {
    $user = makeProgrammeAdmin('APPRG02');

    $this->actingAs($user)
        ->post(route('crm.applications.programmes.store'), [
            'name' => 'B.Tech Computer Science',
            'code' => 'BTCS',
            'level' => 'UG',
            'department' => 'Engineering',
            'is_active' => true,
        ])
        ->assertRedirect(route('crm.applications.programmes.index'));

    $this->assertDatabaseHas('crm_programmes', [
        'institution_id' => $user->institution_id,
        'name' => 'B.Tech Computer Science',
        'code' => 'BTCS',
        'is_active' => true,
    ]);

    $record = CrmProgramme::withoutGlobalScopes()->where('institution_id', $user->institution_id)->first();
    expect($record?->erp_programme_uuid)->not->toBeNull();
});
