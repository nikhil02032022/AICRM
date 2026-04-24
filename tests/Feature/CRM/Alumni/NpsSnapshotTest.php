<?php

declare(strict_types=1);

// BRD: CRM-AL-004 — Feature tests for NPS snapshot (manual web + webhook)

use App\Enums\CRM\Alumni\NpsSnapshotSource;
use App\Models\CRM\Admin\AcademicYear;
use App\Models\CRM\Alumni\AlumniNpsSnapshot;
use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'alumni.nps.manage', 'guard_name' => 'web']);
    $adminRole = Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);
    $adminRole->givePermissionTo('alumni.nps.manage');

    $this->institution = Institution::factory()->create();

    $this->admin = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->admin->assignRole('institution-admin');

    $this->academicYear = AcademicYear::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'label'          => '2025-2026',
        'start_date'     => '2025-06-01',
        'end_date'       => '2026-05-31',
        'is_active'      => true,
        'status'         => 'active',
    ]);
});

it('authenticated admin can POST a manual NPS snapshot via web UI and it is persisted', function (): void {
    $response = $this->actingAs($this->admin)
        ->post(route('crm.admin.nps.store'), [
            'academic_year_id' => $this->academicYear->id,
            'programme_id'     => null,
            'promoters_pct'    => 65,
            'neutrals_pct'     => 20,
            'detractors_pct'   => 15,
            'survey_date'      => '2026-03-01',
        ]);

    $response->assertRedirect(route('crm.admin.nps.index'));

    $snapshot = AlumniNpsSnapshot::withoutGlobalScopes()
        ->where('institution_id', $this->institution->id)
        ->where('survey_date', '2026-03-01')
        ->first();

    expect($snapshot)->not->toBeNull();
    expect($snapshot->nps_score)->toBe(50);
    expect($snapshot->source->value)->toBe(NpsSnapshotSource::Manual->value);
});

it('NPS webhook endpoint accepts valid payload and creates snapshot with source=webhook', function (): void {
    Sanctum::actingAs($this->admin, ['*']);

    $response = $this->postJson('/api/crm/v1/alumni/nps-sync', [
        'institution_id'   => $this->institution->id,
        'academic_year_id' => $this->academicYear->id,
        'programme_id'     => null,
        'promoters_pct'    => 70,
        'neutrals_pct'     => 20,
        'detractors_pct'   => 10,
        'survey_date'      => '2026-04-01',
    ]);

    $response->assertStatus(201)
             ->assertJson(['success' => true, 'nps_score' => 60]);

    $snapshot = AlumniNpsSnapshot::withoutGlobalScopes()
        ->where('institution_id', $this->institution->id)
        ->where('survey_date', '2026-04-01')
        ->first();

    expect($snapshot)->not->toBeNull();
    expect($snapshot->source->value)->toBe(NpsSnapshotSource::Webhook->value);
});

it('NPS webhook endpoint returns 401 when no Sanctum token is provided', function (): void {
    $response = $this->postJson('/api/crm/v1/alumni/nps-sync', [
        'institution_id'   => $this->institution->id,
        'academic_year_id' => $this->academicYear->id,
        'promoters_pct'    => 60,
        'neutrals_pct'     => 20,
        'detractors_pct'   => 20,
        'survey_date'      => '2026-04-01',
    ]);

    $response->assertStatus(401);
});
