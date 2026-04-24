<?php

declare(strict_types=1);

namespace App\Services\CRM\System;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// NFR-AV-001 — Health check service for load balancer probes.
// Returns structured status per component; exposes no sensitive internals.
class HealthCheckService
{
    /**
     * @return array{status: 'ok'|'degraded', checks: array<string, array{status: 'ok'|'fail', latency_ms?: int, message?: string}>, timestamp: string}
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis'    => $this->checkRedis(),
            'queue'    => $this->checkQueue(),
        ];

        $overall = collect($checks)->every(fn ($c) => $c['status'] === 'ok') ? 'ok' : 'degraded';

        return [
            'status'    => $overall,
            'checks'    => $checks,
            'timestamp' => now()->toISOString(),
        ];
    }

    /** @return array{status: 'ok'|'fail', latency_ms: int} */
    private function checkDatabase(): array
    {
        $start = hrtime(true);
        try {
            DB::select('SELECT 1');
            $ms = (int) round((hrtime(true) - $start) / 1_000_000);

            return ['status' => 'ok', 'latency_ms' => $ms];
        } catch (\Throwable) {
            return ['status' => 'fail', 'latency_ms' => 0];
        }
    }

    /** @return array{status: 'ok'|'fail', latency_ms: int} */
    private function checkRedis(): array
    {
        $start = hrtime(true);
        try {
            Cache::store('redis')->get('_health_probe');
            $ms = (int) round((hrtime(true) - $start) / 1_000_000);

            return ['status' => 'ok', 'latency_ms' => $ms];
        } catch (\Throwable) {
            return ['status' => 'fail', 'latency_ms' => 0];
        }
    }

    /** @return array{status: 'ok'|'fail', pending_jobs: int, failed_jobs: int} */
    private function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();

            return ['status' => 'ok', 'pending_jobs' => $pending, 'failed_jobs' => $failed];
        } catch (\Throwable) {
            return ['status' => 'fail', 'pending_jobs' => 0, 'failed_jobs' => 0];
        }
    }
}
