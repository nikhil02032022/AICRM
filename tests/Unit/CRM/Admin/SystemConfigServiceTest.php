<?php

declare(strict_types=1);

// BRD: CRM-SA-006 — System configuration (timezone, locale, branding, business hours)

use App\Models\CRM\Institution;
use App\Models\User;
use App\Services\CRM\Admin\SystemConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->user        = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->service     = app(SystemConfigService::class);
});

it('set() creates config and get() retrieves it', function (): void {
    $this->actingAs($this->user);

    $this->service->set('site_name', 'Demo Uni', 'string', $this->institution->id);

    $value = $this->service->get('site_name', $this->institution->id);

    expect($value)->toBe('Demo Uni');
});

it('getGroup() returns all keys with prefix', function (): void {
    $this->actingAs($this->user);

    $this->service->set('branding.color', '#fff', 'string', $this->institution->id);
    $this->service->set('branding.logo', 'http://example.com/logo.png', 'string', $this->institution->id);

    $group = $this->service->getGroup('branding', $this->institution->id);

    expect($group)->toBeArray();
    expect(count($group))->toBe(2);
    expect($group)->toHaveKey('branding.color');
    expect($group)->toHaveKey('branding.logo');
});

it('get() returns default when key does not exist', function (): void {
    $value = $this->service->get('nonexistent_key', $this->institution->id, 'fallback');

    expect($value)->toBe('fallback');
});

it('set() updates existing config on second call', function (): void {
    $this->actingAs($this->user);

    $this->service->set('timezone', 'Asia/Kolkata', 'string', $this->institution->id);
    $this->service->set('timezone', 'UTC', 'string', $this->institution->id);

    $value = $this->service->get('timezone', $this->institution->id);

    expect($value)->toBe('UTC');

    $this->assertDatabaseCount('system_configs', 1);
});
