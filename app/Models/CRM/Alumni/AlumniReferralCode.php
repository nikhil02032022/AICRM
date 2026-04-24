<?php

declare(strict_types=1);

namespace App\Models\CRM\Alumni;

use App\Enums\CRM\Alumni\ReferralRewardStatus;
use App\Models\CRM\Lead;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// BRD: CRM-AL-002 — Unique per-alumni referral code linked to a campaign
class AlumniReferralCode extends Model
{
    protected $table = 'alumni_referral_codes';

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'campaign_id',
        'alumni_id',
        'code',
        'is_active',
        'conversions_count',
        'reward_status',
        'shared_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_status'     => ReferralRewardStatus::class,
            'is_active'         => 'boolean',
            'conversions_count' => 'integer',
            'shared_at'         => 'datetime',
            'expires_at'        => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AlumniReferralCampaign::class, 'campaign_id');
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(AlumniPipeline::class, 'alumni_id');
    }

    // BRD: CRM-AL-003 — Leads attributed through this referral code
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'referral_code', 'code')
                    ->where('leads.institution_id', $this->institution_id);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
