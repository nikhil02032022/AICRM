<?php

declare(strict_types=1);

// BRD: CRM-SA-004 — System health API tests
// Covers: snapshot structure, component coverage, RBAC, caching, no PII in response, history endpoint

use App\Models\CRM\Institution;
use App\Models\CRM\SystemHealthLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seed(PermissionSeeder::class);
});

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeHealthAdmin(string $suffix = 'a'): array
{
    $institution = Institution::create([
        'name'      => 'Health Inst ' . $suffix,
        'code'      => 'HLT' . strtoupper($suffix),
        'is_active' => true,
    ]);

    $admin = User::create([
        'name'           => 'Health Admin ' . $suffix,
        'email'          => 'health-' . $suffix . '@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $admin->givePermissionTo('crm.admin.system-health.view');

    return [$institution, $admin];
}

function seedHealthLogs(string $component = 'database', int $count = 3): void
{
    for ($i = 0; $i < $count; $i++) {
        SystemHealthLog::create([
            'component'    => $component,
            'status'       => 'ok',
            'metric_name'  => 'response_time_ms',
            'metric_value' => strval(rand(10, 200)),
            'recorded_at'  => now()->subMinutes($i * 10),
        ]);
    }
}

// ─── SNAPSHOT STRUCTURE ───────────────────────────────────────────────────────

it('snapshot endpoint returns success=true with data array (CRM-SA-004)', function (): void {
    [, $admin] = makeHealthAdmin();

    actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/admin/system-health')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'data']);
});

it('each component entry has required fields (CRM-SA-004)', function (): void {
    [, $admin] = makeHealthAdmin('b');

    seedHealthLogs('database');
    seedHealthLogs('redis');
    seedHealthLogs('queue');

    $response = actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/admin/system-health')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->not->toBeEmpty();

    // Snapshot is a keyed object: { database: {...}, redis: {...}, ... }
    foreach ($data as $componentKey => $entry) {
        expect($entry)->toHaveKeys(['status', 'metric_name', 'metric_value', 'recorded_at']);
        expect($componentKey)->toBeString();
    }
});

it('snapshot response contains no PII fields (CRM-SA-004 DPDP)', function (): void {
    [, $admin] = makeHealthAdmin('c');

    seedHealthLogs();

    $response = actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/admin/system-health')
        ->assertOk();

    $raw = json_encode($response->json());

    // Ensure no user-identifiable fields leak into health logs
    expect($raw)->not->toContain('email');
    expect($raw)->not->toContain('mobile');
    expect($raw)->not->toContain('password');
    expect($raw)->not->toContain('aadhaar');
});

// ─── CACHING ─────────────────────────────────────────────────────────────────

it('two consecutive snapshot calls hit same cache — no duplicate DB inserts (CRM-SA-004)', function (): void {
    [, $admin] = makeHealthAdmin('d');

    seedHealthLogs('database', 2);

    Cache::flush(); // ensure clean cache state

    actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/admin/system-health')
        ->assertOk();

    $countAfterFirst = SystemHealthLog::count();

    actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/admin/system-health')
        ->assertOk();

    $countAfterSecond = SystemHealthLog::count();

    // Second call should not write new rows (result came from cache)
    expect($countAfterSecond)->toBe($countAfterFirst);
});

// ─── HISTORY ENDPOINT ────────────────────────────────────────────────────────

it('history endpoint returns logs for a specific component (CRM-SA-004)', function (): void {
    [, $admin] = makeHealthAdmin('e');

    seedHealthLogs('database', 5);
    seedHealthLogs('redis', 2);

    actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/admin/system-health/database/history')
        ->assertOk()
        ->assertJsonStructure(['success', 'data' => [['status', 'metric_value', 'metric_name', 'recorded_at']]]);

    $response = actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/admin/system-health/database/history')
        ->assertOk();

    $items = $response->json('data');
    // All returned items belong to the database component (verified by the route parameter)
    expect($items)->each->toHaveKeys(['status', 'metric_value', 'metric_name', 'recorded_at']);
    expect(count($items))->toBe(5);
});

it('history defaults to 24-hour window (CRM-SA-004)', function (): void {
    [, $admin] = makeHealthAdmin('f');

    // Log within 24h
    SystemHealthLog::create([
        'component'    => 'redis',
        'status'       => 'ok',
        'metric_name'  => 'hit_rate',
        'metric_value' => '98.5',
        'recorded_at'  => now()->subHours(12),
    ]);

    // Log older than 24h — should be excluded
    SystemHealthLog::create([
        'component'    => 'redis',
        'status'       => 'warning',
        'metric_name'  => 'hit_rate',
        'metric_value' => '70.0',
        'recorded_at'  => now()->subHours(30),
    ]);

    $response = actingAs($admin, 'sanctum')
        ->getJson('/api/v1/crm/admin/system-health/redis/history')
        ->assertOk();

    $items = $response->json('data');
    expect(count($items))->toBe(1);
    expect((string) $items[0]['metric_value'])->toBe('98.5');
});

// ─── RBAC ────────────────────────────────────────────────────────────────────

it('user without crm.admin.system-health.view cannot access snapshot (CRM-SA-004)', function (): void {
    [$institution] = makeHealthAdmin('g');

    $noPerms = User::create([
        'name'           => 'No Health Perms',
        'email'          => 'no-health@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    actingAs($noPerms, 'sanctum')
        ->getJson('/api/v1/crm/admin/system-health')
        ->assertForbidden();
});

it('user without crm.admin.system-health.view cannot access history (CRM-SA-004)', function (): void {
    [$institution] = makeHealthAdmin('h');

    $noPerms = User::create([
        'name'           => 'No Health Perms 2',
        'email'          => 'no-health-2@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    actingAs($noPerms, 'sanctum')
        ->getJson('/api/v1/crm/admin/system-health/database/history')
        ->assertForbidden();
});

it('unauthenticated request to snapshot is rejected (CRM-SA-004)', function (): void {
    $this->getJson('/api/v1/crm/admin/system-health')
        ->assertUnauthorized();
});
