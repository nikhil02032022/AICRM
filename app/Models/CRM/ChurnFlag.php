<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ChurnRiskLevel;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-LQ-010 — Stores churn risk snapshots with rationale and signal metadata
class ChurnFlag extends Model
{
    use HasUuids;

    protected $table = 'churn_flags';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'lead_id',
        'risk_level',
        'risk_score',
        'rationale',
        'indicators',
        'flagged_at',
        'mitigated_at',
    ];

    protected function casts(): array
    {
        return [
            'risk_level' => ChurnRiskLevel::class,
            'risk_score' => 'integer',
            'indicators' => 'array',
            'flagged_at' => 'datetime',
            'mitigated_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
