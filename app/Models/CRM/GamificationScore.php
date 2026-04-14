<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\PeriodType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * BRD: CRM-EC-010 — Gamification score model for counsellor performance tracking
 * 
 * @property int $id
 * @property string $uuid
 * @property int $institution_id
 * @property int|null $campus_id
 * @property int $user_id
 * @property int $leads_handled
 * @property int $leads_converted
 * @property float $conversion_rate
 * @property int $avg_response_time_minutes
 * @property float $student_satisfaction_score
 * @property int $calls_made
 * @property int $emails_sent
 * @property int $meetings_scheduled
 * @property int $applications_submitted
 * @property int $total_points
 * @property int $streak_days
 * @property \Carbon\Carbon|null $last_activity_date
 * @property PeriodType $period_type
 * @property \Carbon\Carbon $period_start
 * @property \Carbon\Carbon $period_end
 */
class GamificationScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_gamification_scores';

    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'user_id',
        'leads_handled',
        'leads_converted',
        'conversion_rate',
        'avg_response_time_minutes',
        'student_satisfaction_score',
        'calls_made',
        'emails_sent',
        'meetings_scheduled',
        'applications_submitted',
        'total_points',
        'streak_days',
        'last_activity_date',
        'period_type',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'institution_id' => 'integer',
        'campus_id' => 'integer',
        'user_id' => 'integer',
        'leads_handled' => 'integer',
        'leads_converted' => 'integer',
        'conversion_rate' => 'decimal:2',
        'avg_response_time_minutes' => 'integer',
        'student_satisfaction_score' => 'decimal:2',
        'calls_made' => 'integer',
        'emails_sent' => 'integer',
        'meetings_scheduled' => 'integer',
        'applications_submitted' => 'integer',
        'total_points' => 'integer',
        'streak_days' => 'integer',
        'last_activity_date' => 'date',
        'period_type' => PeriodType::class,
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (GamificationScore $score) {
            if (empty($score->uuid)) {
                $score->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Calculate conversion rate from leads handled and converted
     */
    public function calculateConversionRate(): float
    {
        if ($this->leads_handled === 0) {
            return 0.0;
        }

        return round(($this->leads_converted / $this->leads_handled) * 100, 2);
    }
}
