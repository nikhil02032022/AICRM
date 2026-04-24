<?php

declare(strict_types=1);

// BRD: CRM-AL-002 — Feature tests for alumni referral campaign CRUD

use App\Enums\CRM\Alumni\ReferralCampaignStatus;
use App\Models\CRM\Alumni\AlumniReferralCampaign;
use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Seed the required permissions for Group Z
    Permission::firstOrCreate(['name' => 'alumni.referral.view',   'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'alumni.referral.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'alumni.nps.manage',      'guard_name' => 'web']);

    $adminRole    = Role::firstOrCreate(['name' => 'institution-admin',    'guard_name' => 'web']);
    $counsellorRole = Role::firstOrCreate(['name' => 'senior-counsellor', 'guard_name' => 'web']);

    $adminRole->givePermissionTo(['alumni.referral.view', 'alumni.referral.manage']);
    $counsellorRole->givePermissionTo('alumni.referral.view');

    $this->institution = Institution::factory()->create();

    $this->admin = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->admin->assignRole('institution-admin');

    $this->counsellor = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->counsellor->assignRole('senior-counsellor');
});

it('institution-admin can create a referral campaign', function (): void {
    $response = $this->actingAs($this->admin)
        ->post(route('crm.alumni.referral.campaigns.store'), [
            'name'         => 'MBA Batch 2026 Referral',
            'description'  => 'Annual referral campaign',
            'start_date'   => '2026-05-01',
            'end_date'     => '2026-12-31',
            'reward_type'  => 'gift_voucher',
            'reward_value' => 2000,
        ]);

    $response->assertRedirect();

    expect(
        AlumniReferralCampaign::withoutGlobalScopes()
            ->where('institution_id', $this->institution->id)
            ->where('name', 'MBA Batch 2026 Referral')
            ->exists()
    )->toBeTrue();
});

it('senior-counsellor without manage permission receives 403 on campaign store', function (): void {
    $response = $this->actingAs($this->counsellor)
        ->post(route('crm.alumni.referral.campaigns.store'), [
            'name'        => 'Unauthorized Campaign',
            'start_date'  => '2026-05-01',
            'reward_type' => 'recognition',
        ]);

    $response->assertStatus(403);
});

it('campaign store validates that start_date is not after end_date', function (): void {
    $response = $this->actingAs($this->admin)
        ->post(route('crm.alumni.referral.campaigns.store'), [
            'name'        => 'Bad Dates Campaign',
            'start_date'  => '2026-12-01',
            'end_date'    => '2026-01-01', // before start_date
            'reward_type' => 'fee_waiver',
        ]);

    $response->assertSessionHasErrors('end_date');
});
