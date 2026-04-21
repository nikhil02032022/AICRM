<?php

declare(strict_types=1);

namespace App\Models\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ScholarshipType;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-FM-006 — Scholarship category
class ScholarshipCategory extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'scholarship_categories';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid', 'institution_id', 'campus_id', 'programme_id',
        'code', 'name', 'type', 'computation', 'value', 'max_cap',
        'is_active', 'effective_from', 'effective_to',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ScholarshipType::class,
            'value' => 'decimal:2',
            'max_cap' => 'decimal:2',
            'is_active' => 'bool',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(CrmProgramme::class, 'programme_id');
    }

    public function eligibilityRules(): HasMany
    {
        return $this->hasMany(ScholarshipEligibilityRule::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(ScholarshipAward::class);
    }
}
