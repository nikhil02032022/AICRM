<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\SystemHealthComponent;
use App\Enums\CRM\SystemHealthStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// BRD: CRM-SA-011 — Periodic health probe snapshots (no PII, no institution scope)
class SystemHealthLog extends Model
{
    use HasUuids;

    protected $table = 'system_health_logs';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'component',
        'status',
        'metric_name',
        'metric_value',
        'metadata',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'component'    => SystemHealthComponent::class,
            'status'       => SystemHealthStatus::class,
            'metric_value' => 'float',
            'metadata'     => 'array',
            'recorded_at'  => 'datetime',
        ];
    }
}
