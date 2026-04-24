<?php

declare(strict_types=1);

// NFR-SE-005 — AdminIpWhitelist middleware: allow all when empty, block non-listed IPs, exempt /health

use App\Console\Commands\CRM\Admin\ClearIpWhitelistCommand;
use App\Http\Middleware\CRM\AdminIpWhitelist;
use App\Models\CRM\Institution;
use App\Models\User;
use App\Services\CRM\Admin\SystemConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Route redis to array so health check passes in tests
    config(['cache.stores.redis' => ['driver' => 'array']]);
    Cache::purge('redis');

    Permission::firstOrCreate(['name' => 'crm.admin.access', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);

    $this->institution = Institution::factory()->create();
    $this->admin       = User::factory()->create([
        'institution_id' => $this->institution->id,
        'mfa_enabled'    => true,
    ]);
    $this->admin->givePermissionTo('crm.admin.access');
    $this->admin->assignRole('institution-admin');
});

test('admin route passes when IP whitelist is empty', function (): void {
    // No whitelist configured — middleware should pass all requests
    $this->actingAs($this->admin)
        ->withSession(['mfa_verified' => true])
        ->get('/crm/admin/system-config')
        ->assertDontSee('IP address is not whitelisted');
});

test('admin route is blocked with 403 for IP not in whitelist', function (): void {
    /** @var SystemConfigService $configService */
    $configService = app(SystemConfigService::class);
    $configService->set('admin_ip_whitelist', '10.0.0.1', 'string', $this->institution->id);

    $this->actingAs($this->admin)
        ->withSession(['mfa_verified' => true])
        ->get('/crm/admin/system-config')
        ->assertForbidden();
});

test('admin route is allowed when request IP is in whitelist', function (): void {
    /** @var SystemConfigService $configService */
    $configService = app(SystemConfigService::class);
    $configService->set('admin_ip_whitelist', '10.10.10.10', 'string', $this->institution->id);

    // Test the middleware directly with a controlled request IP
    $request = Request::create('/crm/admin/test', 'GET');
    $request->server->set('REMOTE_ADDR', '10.10.10.10');
    $request->setUserResolver(fn () => $this->admin);

    $middleware = app(AdminIpWhitelist::class);
    $passed    = false;
    $middleware->handle($request, function () use (&$passed) {
        $passed = true;

        return response('ok');
    });

    expect($passed)->toBeTrue();
});

test('GET /health bypasses IP whitelist and returns 200', function (): void {
    /** @var SystemConfigService $configService */
    $configService = app(SystemConfigService::class);
    $configService->set('admin_ip_whitelist', '10.0.0.1', 'string', $this->institution->id);

    $this->get('/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok');
});

test('crm:admin:clear-ip-whitelist command clears whitelist for given institution', function (): void {
    /** @var SystemConfigService $configService */
    $configService = app(SystemConfigService::class);
    $configService->set('admin_ip_whitelist', '10.0.0.1', 'string', $this->institution->id);

    $this->artisan(ClearIpWhitelistCommand::class, ['--institution' => $this->institution->id])
        ->assertSuccessful();

    $stored = $configService->get('admin_ip_whitelist', $this->institution->id, '');
    expect($stored)->toBeEmpty();
});
