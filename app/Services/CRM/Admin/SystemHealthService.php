<?php

declare(strict_types=1);

namespace App\Services\CRM\Admin;

use App\Enums\CRM\SystemHealthComponent;
use App\Enums\CRM\SystemHealthStatus;
use App\Models\CRM\SystemHealthLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

// BRD: CRM-SA-011 — Service that probes each system component and records status snapshots
final class SystemHealthService
{
    // Cache TTL for the latest health snapshot (30 seconds)
    private const CACHE_TTL = 30;

    /**
     * BRD: CRM-SA-011 — Run all probes and return latest status per component.
     *
     * @return array<string, array{status: string, label: string, metric_value: float|null, metric_name: string, recorded_at: string, badge_class: string}>
     */
    public function getLatestSnapshot(): array
    {
        return Cache::remember('system_health_snapshot', self::CACHE_TTL, function (): array {
            $probes = $this->runAllProbes();
            $this->persistProbes($probes);

            return $probes;
        });
    }

    /**
     * BRD: CRM-SA-011 — Retrieve historical health logs for trending.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(string $component, int $hours = 24): array
    {
        return SystemHealthLog::where('component', $component)
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'asc')
            ->get(['status', 'metric_value', 'metric_name', 'recorded_at'])
            ->toArray();
    }

    /** @return array<string, array<string, mixed>> */
    private function runAllProbes(): array
    {
        return [
            SystemHealthComponent::DATABASE->value    => $this->probeDatabase(),
            SystemHealthComponent::REDIS->value       => $this->probeRedis(),
            SystemHealthComponent::QUEUE->value       => $this->probeQueue(),
            SystemHealthComponent::HORIZON->value     => $this->probeHorizon(),
            SystemHealthComponent::S3->value          => $this->probeS3(),
            SystemHealthComponent::AI_API->value      => $this->probeAiApi(),
            SystemHealthComponent::MAIL->value        => $this->probeMail(),
            SystemHealthComponent::SMS_GATEWAY->value => $this->probeSmsGateway(),
        ];
    }

    /** @return array<string, mixed> */
    private function probeDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);
            $status  = $latency > 200 ? SystemHealthStatus::WARNING : SystemHealthStatus::OK;

            return $this->make($status, 'latency_ms', $latency, SystemHealthComponent::DATABASE);
        } catch (Throwable $e) {
            Log::error('DB health probe failed', ['error' => $e->getMessage()]);

            return $this->make(SystemHealthStatus::CRITICAL, 'latency_ms', null, SystemHealthComponent::DATABASE);
        }
    }

    /** @return array<string, mixed> */
    private function probeRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);
            $status  = $latency > 50 ? SystemHealthStatus::WARNING : SystemHealthStatus::OK;

            return $this->make($status, 'latency_ms', $latency, SystemHealthComponent::REDIS);
        } catch (Throwable) {
            return $this->make(SystemHealthStatus::CRITICAL, 'latency_ms', null, SystemHealthComponent::REDIS);
        }
    }

    /** @return array<string, mixed> */
    private function probeQueue(): array
    {
        try {
            /** @disregard P1013 */
            $depth = Queue::size('default') + Queue::size('crm-leads') + Queue::size('crm-analytics');

            $status = match (true) {
                $depth > 5000 => SystemHealthStatus::CRITICAL,
                $depth > 500  => SystemHealthStatus::WARNING,
                default       => SystemHealthStatus::OK,
            };

            return $this->make($status, 'queue_depth', (float) $depth, SystemHealthComponent::QUEUE);
        } catch (Throwable) {
            return $this->make(SystemHealthStatus::UNKNOWN, 'queue_depth', null, SystemHealthComponent::QUEUE);
        }
    }

    /** @return array<string, mixed> */
    private function probeHorizon(): array
    {
        try {
            $status = Cache::get('horizon:status', 'inactive');
            $hs     = $status === 'paused' ? SystemHealthStatus::WARNING :
                      ($status === 'running' ? SystemHealthStatus::OK : SystemHealthStatus::CRITICAL);

            return $this->make($hs, 'horizon_status', null, SystemHealthComponent::HORIZON, ['raw_status' => $status]);
        } catch (Throwable) {
            return $this->make(SystemHealthStatus::UNKNOWN, 'horizon_status', null, SystemHealthComponent::HORIZON);
        }
    }

    /** @return array<string, mixed> */
    private function probeS3(): array
    {
        try {
            // BRD: CRM-SA-011 — Probe S3 with a lightweight head object check, not a full write
            $start  = microtime(true);
            Storage::disk('s3')->exists('health-check.txt');
            $latency = round((microtime(true) - $start) * 1000, 2);
            $status  = $latency > 1000 ? SystemHealthStatus::WARNING : SystemHealthStatus::OK;

            return $this->make($status, 'latency_ms', $latency, SystemHealthComponent::S3);
        } catch (Throwable) {
            return $this->make(SystemHealthStatus::CRITICAL, 'latency_ms', null, SystemHealthComponent::S3);
        }
    }

    /** @return array<string, mixed> */
    private function probeAiApi(): array
    {
        // BRD: CRM-SA-011 — AI API health is tracked via the ai_usage_logs failure rate
        try {
            $failRate = \App\Models\CRM\AiUsageLog::where('created_at', '>=', now()->subMinutes(5))
                ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) / COUNT(*) * 100 AS rate')
                ->value('rate') ?? 0;

            $failRate = (float) $failRate;
            $status   = match (true) {
                $failRate > 50 => SystemHealthStatus::CRITICAL,
                $failRate > 10 => SystemHealthStatus::WARNING,
                default        => SystemHealthStatus::OK,
            };

            return $this->make($status, 'error_rate_pct', $failRate, SystemHealthComponent::AI_API);
        } catch (Throwable) {
            return $this->make(SystemHealthStatus::UNKNOWN, 'error_rate_pct', null, SystemHealthComponent::AI_API);
        }
    }

    /** @return array<string, mixed> */
    private function probeMail(): array
    {
        // Mail probe: check failure rate in communication_logs for email channel in last 5 min
        try {
            $failCount = \App\Models\CRM\CommunicationLog::where('channel', 'email')
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();

            $status = $failCount > 50 ? SystemHealthStatus::WARNING : SystemHealthStatus::OK;

            return $this->make($status, 'recent_failures', (float) $failCount, SystemHealthComponent::MAIL);
        } catch (Throwable) {
            return $this->make(SystemHealthStatus::UNKNOWN, 'recent_failures', null, SystemHealthComponent::MAIL);
        }
    }

    /** @return array<string, mixed> */
    private function probeSmsGateway(): array
    {
        try {
            $failCount = \App\Models\CRM\CommunicationLog::where('channel', 'sms')
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();

            $status = $failCount > 50 ? SystemHealthStatus::WARNING : SystemHealthStatus::OK;

            return $this->make($status, 'recent_failures', (float) $failCount, SystemHealthComponent::SMS_GATEWAY);
        } catch (Throwable) {
            return $this->make(SystemHealthStatus::UNKNOWN, 'recent_failures', null, SystemHealthComponent::SMS_GATEWAY);
        }
    }

    /**
     * @param array<string, mixed>|null $extra
     * @return array<string, mixed>
     */
    private function make(
        SystemHealthStatus $status,
        string $metricName,
        ?float $metricValue,
        SystemHealthComponent $component,
        ?array $extra = null,
    ): array {
        return [
            'status'       => $status->value,
            'label'        => $status->label(),
            'badge_class'  => $status->tailwindBadgeClass(),
            'metric_name'  => $metricName,
            'metric_value' => $metricValue,
            'component'    => $component->label(),
            'recorded_at'  => now()->toIso8601String(),
            'metadata'     => $extra,
        ];
    }

    /** @param array<string, array<string, mixed>> $probes */
    private function persistProbes(array $probes): void
    {
        $now = now();
        $rows = [];

        foreach ($probes as $componentKey => $probe) {
            $rows[] = [
                'uuid'         => (string) \Illuminate\Support\Str::uuid(),
                'component'    => $componentKey,
                'status'       => $probe['status'],
                'metric_name'  => $probe['metric_name'],
                'metric_value' => $probe['metric_value'],
                'metadata'     => json_encode($probe['metadata']),
                'recorded_at'  => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        DB::table('system_health_logs')->insert($rows);
    }
}
