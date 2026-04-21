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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-FM-009 — Installment plan template
class FeeInstallmentPlan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'fee_installment_plans';

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
        'name', 'fee_type', 'total_amount', 'schedule', 'is_active',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fee_type' => FeeType::class,
            'total_amount' => 'decimal:2',
            'schedule' => 'array',
            'is_active' => 'bool',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(CrmProgramme::class, 'programme_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ApplicationInstallmentSchedule::class);
    }
}
