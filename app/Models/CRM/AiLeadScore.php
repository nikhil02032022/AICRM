<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\AI\ConfidenceLevel;
use App\Enums\CRM\AI\PredictionStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-LQ-003, CRM-AI-001 — Stores AI-assisted score and Claude API conversion probability snapshots
class AiLeadScore extends Model
{
    use HasUuids;

    protected $table = 'ai_lead_scores';

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
        'score',
        'explanation',
        'model_version',
        'metadata',
        'calculated_at',
        // CRM-AI-001 conversion prediction columns
        'conversion_probability',
        'confidence_score',
        'prediction_factors',
        'prediction_refreshed_at',
        'prediction_status',
    ];

    protected function casts(): array
    {
        return [
            'score'                   => 'integer',
            'metadata'                => 'array',
            'calculated_at'           => 'datetime',
            'conversion_probability'  => 'decimal:4',
            'confidence_score'        => 'decimal:4',
            'prediction_factors'      => 'array',
            'prediction_refreshed_at' => 'datetime',
            'prediction_status'       => PredictionStatus::class,
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function conversionConfidenceLevel(): ?ConfidenceLevel
    {
        if ($this->confidence_score === null) {
            return null;
        }

        return ConfidenceLevel::fromScore((float) $this->confidence_score);
    }

    public function conversionPercentage(): ?string
    {
        if ($this->conversion_probability === null) {
            return null;
        }

        return number_format((float) $this->conversion_probability * 100, 1).'%';
    }
}
