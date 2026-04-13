<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// BRD: CRM-AI-010 — Stores AI-suggested nurture journey blueprints by audience segment
class NbaJourney extends Model
{
    use HasUuids;

    protected $table = 'nba_journeys';

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
        'segment_key',
        'segment_label',
        'confidence_score',
        'rationale',
        'steps',
        'metadata',
        'model_version',
        'generated_for_date',
        'suggested_at',
        'applied_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'confidence_score' => 'integer',
            'steps' => 'array',
            'metadata' => 'array',
            'generated_for_date' => 'date',
            'suggested_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }
}
