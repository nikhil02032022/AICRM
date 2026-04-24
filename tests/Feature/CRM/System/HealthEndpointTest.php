<?php

declare(strict_types=1);

// NFR-AV-001 — GET /health endpoint: returns 200 on healthy, 503 on degraded

use App\Services\CRM\System\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.stores.redis' => ['driver' => 'array']]);
    Cache::purge('redis');
});

test('GET /health returns 200 JSON with status ok when all services healthy', function (): void {
    $this->get('/health')
        ->assertOk()
        ->assertJson(['status' => 'ok'])
        ->assertJsonStructure([
            'status',
            'checks' => [
                'database' => ['status', 'latency_ms'],
                'redis'    => ['status', 'latency_ms'],
                'queue'    => ['status', 'pending_jobs', 'failed_jobs'],
            ],
            'timestamp',
        ]);
});

test('GET /health does not expose sensitive internal information', function (): void {
    $response = $this->get('/health');
    $content  = $response->getContent();

    expect($content)->not->toContain('APP_KEY')
        ->and($content)->not->toContain('password')
        ->and($content)->not->toContain('DB_PASSWORD')
        ->and($content)->not->toContain('stack trace');
});

test('GET /health returns 503 when database is unavailable', function (): void {
    $mock = $this->mock(HealthCheckService::class);
    $mock->shouldReceive('check')->andReturn([
        'status'    => 'degraded',
        'checks'    => [
            'database' => ['status' => 'fail', 'latency_ms' => 0],
            'redis'    => ['status' => 'ok', 'latency_ms' => 1],
            'queue'    => ['status' => 'ok', 'pending_jobs' => 0, 'failed_jobs' => 0],
        ],
        'timestamp' => now()->toISOString(),
    ]);

    $this->get('/health')
        ->assertStatus(503)
        ->assertJson(['status' => 'degraded']);
});

test('GET /health is accessible without authentication', function (): void {
    // No auth middleware — should not get 401 or redirect to login
    $response = $this->get('/health');
    expect($response->getStatusCode())->not->toBe(401)->not->toBe(302);
});
