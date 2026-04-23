<?php

declare(strict_types=1);

namespace App\Models\CRM\Agents;

use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AG-002 — Unique referral code per agent for lead source attribution
class AgentReferralCode extends Model
{
    use HasUuids;

    protected $table = 'agent_referral_codes';

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
        'institution_id',
        'code',
        'url_slug',
        'total_leads',
        'total_conversions',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'total_leads'       => 'integer',
            'total_conversions' => 'integer',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function referralUrl(): string
    {
        return url('/leads/capture?ref=' . $this->code);
    }
}
