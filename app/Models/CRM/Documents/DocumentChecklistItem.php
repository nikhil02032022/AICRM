<?php

declare(strict_types=1);

namespace App\Models\CRM\Documents;

use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-DM-001
class DocumentChecklistItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'document_checklist_items';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid', 'institution_id', 'document_checklist_id',
        'code', 'label', 'is_mandatory',
        'max_size_kb', 'allowed_mime', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'bool',
            'allowed_mime' => 'array',
            'max_size_kb'  => 'int',
            'sort_order'   => 'int',
        ];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(DocumentChecklist::class, 'document_checklist_id');
    }
}
