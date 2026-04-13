<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AI-005 — Daily AI-prioritised lead ranking per counsellor
class CounsellorPriorityLead extends Model
{
    use HasUuids;

    protected $table = 'counsellor_priority_leads';

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
        'counsellor_id',
        'lead_id',
        'priority_rank',
        'priority_score',
        'reasoning',
        'factors',
        'generated_for_date',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'priority_rank' => 'integer',
            'priority_score' => 'integer',
            'factors' => 'array',
            'generated_for_date' => 'date',
            'generated_at' => 'datetime',
        ];
    }

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counsellor_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
