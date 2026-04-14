<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\BadgeCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * BRD: CRM-EC-010 — Badge model for gamification
 * 
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property string $color
 * @property BadgeCategory $category
 * @property array $criteria
 * @property int $points
 * @property bool $is_active
 */
class Badge extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_badges';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'category',
        'criteria',
        'points',
        'is_active',
    ];

    protected $casts = [
        'category' => BadgeCategory::class,
        'criteria' => 'array',
        'points' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Badge $badge) {
            if (empty($badge->uuid)) {
                $badge->uuid = (string) Str::uuid();
            }
            if (empty($badge->slug)) {
                $badge->slug = Str::slug($badge->name);
            }
        });
    }

    /**
     * Counsellors who earned this badge
     */
    public function counsellors(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'crm_counsellor_badges',
            'badge_id',
            'user_id'
        )->withPivot('points_earned', 'earned_at', 'criteria_met')
          ->withTimestamps();
    }

    /**
     * Check if criteria is met for given metrics
     */
    public function isCriteriaMet(array $metrics): bool
    {
        foreach ($this->criteria as $key => $value) {
            if (!isset($metrics[$key]) || $metrics[$key] < $value) {
                return false;
            }
        }
        return true;
    }
}
