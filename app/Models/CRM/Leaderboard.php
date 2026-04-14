<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\LeaderboardTrend;
use App\Enums\CRM\PeriodType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * BRD: CRM-EC-010 — Leaderboard model for counsellor rankings
 * 
 * @property int $id
 * @property string $uuid
 * @property int $institution_id
 * @property int|null $campus_id
 * @property int $user_id
 * @property int $rank
 * @property int $total_points
 * @property float $conversion_rate
 * @property int $leads_converted
 * @property PeriodType $period_type
 * @property \Carbon\Carbon $period_start
 * @property \Carbon\Carbon $period_end
 * @property int $rank_change
 * @property LeaderboardTrend $trend
 */
class Leaderboard extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_leaderboards';

    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'user_id',
        'rank',
        'total_points',
        'conversion_rate',
        'leads_converted',
        'period_type',
        'period_start',
        'period_end',
        'rank_change',
        'trend',
    ];

    protected $casts = [
        'institution_id' => 'integer',
        'campus_id' => 'integer',
        'user_id' => 'integer',
        'rank' => 'integer',
        'total_points' => 'integer',
        'conversion_rate' => 'decimal:2',
        'leads_converted' => 'integer',
        'period_type' => PeriodType::class,
        'period_start' => 'date',
        'period_end' => 'date',
        'rank_change' => 'integer',
        'trend' => LeaderboardTrend::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Leaderboard $leaderboard) {
            if (empty($leaderboard->uuid)) {
                $leaderboard->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Determine trend based on rank change
     */
    public function determineTrend(): LeaderboardTrend
    {
        if ($this->rank_change > 0) {
            return LeaderboardTrend::UP;
        } elseif ($this->rank_change < 0) {
            return LeaderboardTrend::DOWN;
        }
        return LeaderboardTrend::STABLE;
    }
}
