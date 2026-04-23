<?php

declare(strict_types=1);

namespace App\Models\CRM\Agents;

use App\Enums\CRM\Agents\CommissionAccrualStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Lead;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AG-005 — Auto-accrued commission record created on enrolment confirmation
class AgentCommissionAccrual extends Model
{
    use HasUuids;

    protected $table = 'agent_commission_accruals';

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);

        // Immutable after Approved or Paid — guard against backdating / overrides
        static::updating(function (self $accrual): void {
            if ($accrual->getOriginal('status') !== null) {
                $originalStatus = CommissionAccrualStatus::from($accrual->getOriginal('status'));
                if ($originalStatus->isImmutable()) {
                    throw new \DomainException('Commission accrual cannot be modified after approval.');
                }
            }
        });
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'agent_id',
        'application_id',
        'lead_id',
        'programme_id',
        'structure_id',
        'accrual_basis_amount',
        'commission_amount',
        'status',
        'accrued_at',
        'reversed_at',
        'notes',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status'               => CommissionAccrualStatus::class,
            'accrual_basis_amount' => 'decimal:2',
            'commission_amount'    => 'decimal:2',
            'accrued_at'           => 'datetime',
            'reversed_at'          => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(CrmProgramme::class, 'programme_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(AgentCommissionStructure::class, 'structure_id');
    }
}
