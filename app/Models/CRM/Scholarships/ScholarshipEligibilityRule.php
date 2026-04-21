<?php

declare(strict_types=1);

namespace App\Models\CRM\Scholarships;

use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-FM-007
class ScholarshipEligibilityRule extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'scholarship_eligibility_rules';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid', 'institution_id', 'scholarship_category_id',
        'attribute', 'operator', 'value', 'combinator', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'sort_order' => 'int',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ScholarshipCategory::class, 'scholarship_category_id');
    }
}
