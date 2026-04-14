<?php

declare(strict_types=1);

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// BRD: CRM-EC-005 — Stores a single custom field value for a lead or application record
class CustomFieldValue extends Model
{
    use HasUuids;

    protected $table = 'custom_field_values';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'custom_field_id',
        'entity_type',
        'entity_id',
        'value',
    ];

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    // Polymorphic relation back to Lead or Application
    public function entity(): MorphTo
    {
        return $this->morphTo('entity');
    }
}
