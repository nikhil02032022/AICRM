<?php

declare(strict_types=1);

namespace App\Models\CRM\Alumni;

use App\Enums\CRM\Alumni\ReferralCampaignStatus;
use App\Enums\CRM\Alumni\ReferralRewardType;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

// BRD: CRM-AL-002 — Alumni referral campaign entity
class AlumniReferralCampaign extends Model
{
    protected $table = 'alumni_referral_campaigns';

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'reward_type',
        'reward_value',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status'       => ReferralCampaignStatus::class,
            'reward_type'  => ReferralRewardType::class,
            'start_date'   => 'date',
            'end_date'     => 'date',
            'reward_value' => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function codes(): HasMany
    {
        return $this->hasMany(AlumniReferralCode::class, 'campaign_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Institution::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    // BRD: CRM-AL-002 — An active campaign: status=Active AND start_date <= today AND (no end_date OR end_date >= today)
    public function scopeActive(Builder $query): void
    {
        $today = Carbon::today();
        $query->where('status', ReferralCampaignStatus::Active->value)
              ->where('start_date', '<=', $today)
              ->where(function (Builder $q) use ($today): void {
                  $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
              });
    }
}
