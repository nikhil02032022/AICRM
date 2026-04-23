<?php

declare(strict_types=1);

namespace App\Models\CRM\Agents;

use App\Enums\CRM\Agents\CommissionStructureType;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

// BRD: CRM-AG-004 — Commission structure (rate + basis) per agent+programme agreement
class AgentCommissionStructure extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'agent_commission_structures';

    /** @return list<string> */
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
        'agent_id',
        'programme_id',
        'institution_id',
        'structure_type',
        'amount',
        'percentage',
        'effective_from',
        'effective_to',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'structure_type' => CommissionStructureType::class,
            'amount'         => 'decimal:2',
            'percentage'     => 'decimal:2',
            'effective_from' => 'date',
            'effective_to'   => 'date',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(CrmProgramme::class, 'programme_id');
    }

    /** Scope: structures effective on the given date. */
    public function scopeActiveAt(Builder $query, Carbon $date): Builder
    {
        return $query
            ->where('effective_from', '<=', $date)
            ->where(function (Builder $q) use ($date): void {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            });
    }
}
