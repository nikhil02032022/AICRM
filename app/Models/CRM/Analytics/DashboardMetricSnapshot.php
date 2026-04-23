<?php

declare(strict_types=1);

namespace App\Models\CRM\Analytics;

use Illuminate\Database\Eloquent\Model;

// BRD: CRM-AR-001, AR-006 — Pre-aggregated daily metric snapshots for performance-critical dashboards
final class DashboardMetricSnapshot extends Model
{
    protected $table = 'dashboard_metric_snapshots';

    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'campus_id',
        'period_date',
        'metric_key',
        'metric_value',
        'segmentation_json',
    ];

    protected function casts(): array
    {
        return [
            'period_date'       => 'date',
            'metric_value'      => 'float',
            'segmentation_json' => 'array',
        ];
    }
}
