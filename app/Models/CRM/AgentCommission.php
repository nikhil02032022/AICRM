<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\CommissionStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-AG-006 — Agent commission record per enrolment conversion
#[ObservedBy(AuditObserver::class)]
class AgentCommission extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'agent_commissions';

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
        'agent_user_id',
        'lead_id',
        'commission_amount',
        'currency',
        'commission_type',
        'percentage_rate',
        'base_amount',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'paid_at',
        'payout_reference',
    ];

    protected function casts(): array
    {
        return [
            'status'            => CommissionStatus::class,
            'commission_amount' => 'decimal:2',
            'percentage_rate'   => 'decimal:2',
            'base_amount'       => 'decimal:2',
            'approved_at'       => 'datetime',
            'paid_at'           => 'datetime',
        ];
    }

    public function agentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
