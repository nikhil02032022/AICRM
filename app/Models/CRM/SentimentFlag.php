<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\SentimentLabel;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AI-004 — Stores inbound sentiment analysis snapshots for lead communication risk
class SentimentFlag extends Model
{
    use HasUuids;

    protected $table = 'sentiment_flags';

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
        'channel',
        'sentiment_label',
        'sentiment_score',
        'is_urgent',
        'rationale',
        'source_excerpt',
        'indicators',
        'model_version',
        'flagged_at',
    ];

    protected function casts(): array
    {
        return [
            'sentiment_label' => SentimentLabel::class,
            'sentiment_score' => 'integer',
            'is_urgent' => 'boolean',
            'indicators' => 'array',
            'flagged_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
