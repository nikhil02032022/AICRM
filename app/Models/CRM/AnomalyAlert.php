<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// BRD: CRM-AI-009 — Detected anomaly alert for lead funnel drop-off signals
class AnomalyAlert extends Model
{
    use HasUuids;

    protected $table = 'anomaly_alerts';

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'alert_type',
        'metric_name',
        'current_value',
        'baseline_value',
        'deviation_percent',
        'threshold_percent',
        'severity',
        'rationale',
        'metadata',
        'model_version',
        'detected_at',
        'resolved_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'current_value' => 'integer',
            'baseline_value' => 'integer',
            'deviation_percent' => 'float',
            'threshold_percent' => 'integer',
            'metadata' => 'array',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
