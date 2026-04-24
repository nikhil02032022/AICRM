<?php

declare(strict_types=1);

// NFR-AV-001 — HealthCheckService unit tests: healthy state and degraded state

use App\Services\CRM\System\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Route Cache::store('redis') to the array driver so tests don't need a real Redis connection
    config(['cache.stores.redis' => ['driver' => 'array']]);
    Cache::purge('redis');

    $this->service = app(HealthCheckService::class);
});

test('check returns ok status when all services are healthy', function (): void {
    $result = $this->service->check();

    expect($result['status'])->toBe('ok')
        ->and($result['checks'])->toHaveKeys(['database', 'redis', 'queue'])
        ->and($result['checks']['database']['status'])->toBe('ok')
        ->and($result['timestamp'])->not->toBeEmpty();
});

test('check returns expected structure with latency_ms for database', function (): void {
    $result = $this->service->check();

    expect($result['checks']['database'])->toHaveKey('latency_ms')
        ->and($result['checks']['database']['latency_ms'])->toBeGreaterThanOrEqual(0);
});

test('check includes pending_jobs and failed_jobs in queue check', function (): void {
    $result = $this->service->check();

    expect($result['checks']['queue'])->toHaveKeys(['status', 'pending_jobs', 'failed_jobs']);
});
