<?php

declare(strict_types=1);

namespace App\Models\CRM\Documents;

use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-DM-001 — Programme-wise document checklist
class DocumentChecklist extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'document_checklists';

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
        'name', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(CrmProgramme::class, 'programme_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentChecklistItem::class)->orderBy('sort_order');
    }
}
