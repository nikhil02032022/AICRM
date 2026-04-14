<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\CustomFieldEntity;
use App\Enums\CRM\CustomFieldType;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-EC-005 — Institution-defined custom field definitions
#[ObservedBy(AuditObserver::class)]
class CustomField extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'custom_fields';

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
        'entity',
        'field_key',
        'label',
        'type',
        'options',
        'is_required',
        'is_visible_in_list',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'entity'              => CustomFieldEntity::class,
            'type'                => CustomFieldType::class,
            'options'             => 'array',
            'is_required'         => 'boolean',
            'is_visible_in_list'  => 'boolean',
            'is_active'           => 'boolean',
            'sort_order'          => 'integer',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'custom_field_id');
    }
}
