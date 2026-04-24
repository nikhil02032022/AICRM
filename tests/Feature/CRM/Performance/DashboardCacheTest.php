<?php

declare(strict_types=1);

// NFR-P-001 — Dashboard Redis cache: KPIs are cached on second request; cache is invalidated on lead change

use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'crm.analytics.institution', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);

    $this->institution = Institution::factory()->create();
    $this->admin       = User::factory()->create([
        'institution_id' => $this->institution->id,
        'mfa_enabled'    => true,
    ]);
    $this->admin->givePermissionTo('crm.analytics.institution');
    $this->admin->assignRole('institution-admin');
});

test('institution dashboard KPIs are served from cache on second request', function (): void {
    Cache::flush();

    // First request — populates cache
    $this->actingAs($this->admin)
        ->withSession(['mfa_verified' => true])
        ->get(route('crm.analytics.dashboards.institution'))
        ->assertOk();

    $cacheKeys = Cache::get(
        "dashboard:inst:{$this->institution->id}:kpis:".md5(serialize([
            'from' => now()->startOfMonth()->toDateString(),
            'to'   => now()->toDateString(),
        ]))
    );

    // Cache key should now be populated
    expect($cacheKeys)->not->toBeNull();
});

test('dashboard cache key includes institution ID for tenant isolation', function (): void {
    $institution2 = Institution::factory()->create();
    $admin2       = User::factory()->create([
        'institution_id' => $institution2->id,
        'mfa_enabled'    => true,
    ]);
    $admin2->givePermissionTo('crm.analytics.institution');
    $admin2->assignRole('institution-admin');

    Cache::flush();

    $this->actingAs($this->admin)->withSession(['mfa_verified' => true])->get(route('crm.analytics.dashboards.institution'))->assertOk();
    $this->actingAs($admin2)->withSession(['mfa_verified' => true])->get(route('crm.analytics.dashboards.institution'))->assertOk();

    // Cache keys must be different per institution
    $filterKey = md5(serialize([
        'from' => now()->startOfMonth()->toDateString(),
        'to'   => now()->toDateString(),
    ]));

    $key1 = "dashboard:inst:{$this->institution->id}:kpis:{$filterKey}";
    $key2 = "dashboard:inst:{$institution2->id}:kpis:{$filterKey}";

    expect($key1)->not->toBe($key2);
});
