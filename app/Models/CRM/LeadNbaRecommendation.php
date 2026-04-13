<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AI-002 — Stores AI next best action recommendations with rationale for counsellor execution
class LeadNbaRecommendation extends Model
{
    use HasUuids;

    protected $table = 'lead_nba_recommendations';

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
        'recommended_action',
        'reasoning',
        'confidence_score',
        'channels',
        'metadata',
        'model_version',
        'generated_at',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'integer',
            'channels' => 'array',
            'metadata' => 'array',
            'generated_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
