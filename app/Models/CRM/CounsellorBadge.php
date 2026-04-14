<?php

declare(strict_types=1);

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * BRD: CRM-EC-010 — Counsellor Badge earned (pivot model)
 * 
 * @property int $id
 * @property string $uuid
 * @property int $institution_id
 * @property int $user_id
 * @property int $badge_id
 * @property int $points_earned
 * @property \Carbon\Carbon $earned_at
 * @property array|null $criteria_met
 */
class CounsellorBadge extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_counsellor_badges';

    protected $fillable = [
        'uuid',
        'institution_id',
        'user_id',
        'badge_id',
        'points_earned',
        'earned_at',
        'criteria_met',
    ];

    protected $casts = [
        'institution_id' => 'integer',
        'user_id' => 'integer',
        'badge_id' => 'integer',
        'points_earned' => 'integer',
        'earned_at' => 'datetime',
        'criteria_met' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CounsellorBadge $counsellorBadge) {
            if (empty($counsellorBadge->uuid)) {
                $counsellorBadge->uuid = (string) Str::uuid();
            }
            if (empty($counsellorBadge->earned_at)) {
                $counsellorBadge->earned_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class, 'badge_id');
    }
}
