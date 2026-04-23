<?php

declare(strict_types=1);

namespace App\Models\CRM\Agents;

use App\Enums\CRM\Agents\AgentStatus;
use App\Models\CRM\Lead;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-AG-001 — Agent/channel partner profile entity
class Agent extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'agents';

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
        'institution_id',
        'name',
        'email',
        'mobile',
        'password',
        'remember_token',
        'agreement_start',
        'agreement_end',
        'status',
        'notes',
    ];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status'          => AgentStatus::class,
            'agreement_start' => 'date',
            'agreement_end'   => 'date',
            'password'        => 'hashed',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function referralCode(): HasOne
    {
        return $this->hasOne(AgentReferralCode::class, 'agent_id');
    }

    public function commissionStructures(): HasMany
    {
        return $this->hasMany(AgentCommissionStructure::class, 'agent_id');
    }

    public function commissionAccruals(): HasMany
    {
        return $this->hasMany(AgentCommissionAccrual::class, 'agent_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'agent_id')->withoutGlobalScopes();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AgentSession::class, 'agent_id');
    }

    // -------------------------------------------------------------------------
    // Domain helpers
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === AgentStatus::Active;
    }
}
