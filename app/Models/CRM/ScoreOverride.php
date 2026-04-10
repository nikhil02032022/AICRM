<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-LQ-007 — Immutable audit record of a counsellor's manual score override
class ScoreOverride extends Model
{
    use HasUuids;

    protected $table = 'score_overrides';

    // Immutable — no updated_at
    public const UPDATED_AT = null;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected $fillable = [
        'uuid',
        'lead_id',
        'overridden_by',
        'previous_score',
        'overridden_score',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'previous_score' => 'integer',
            'overridden_score' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
