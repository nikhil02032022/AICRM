<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AI-003 — Stores AI-generated communication drafts with contextual metadata
class AiMessageDraft extends Model
{
    use HasUuids;

    protected $table = 'ai_message_drafts';

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
        'subject',
        'draft_text',
        'context',
        'metadata',
        'model_version',
        'generated_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'metadata' => 'array',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
