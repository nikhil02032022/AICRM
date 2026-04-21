<?php

declare(strict_types=1);

namespace App\Models\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-FM-001, CRM-FM-002 — Fee structure per programme/fee type
class FeeStructure extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'fee_structures';

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
        'fee_type', 'amount', 'currency',
        'is_active', 'effective_from', 'effective_to',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fee_type' => FeeType::class,
            'amount' => 'decimal:2',
            'is_active' => 'bool',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(CrmProgramme::class, 'programme_id');
    }
}
