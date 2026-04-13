<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\QuestionnaireStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-LQ-009 — Questionnaire definition for lead qualification
class QualificationQuestionnaire extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'qualification_questionnaires';

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
        'name',
        'status',
        'questions',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuestionnaireStatus::class,
            'questions' => 'array',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuestionnaireResponse::class, 'qualification_questionnaire_id');
    }
}
