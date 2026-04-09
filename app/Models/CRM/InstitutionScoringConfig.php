<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-LQ-001, CRM-LQ-005 — Per-institution configurable scoring weights and temperature thresholds
class InstitutionScoringConfig extends Model
{
    use HasUuids;

    protected $table = 'institution_scoring_configs';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // BRD: NFR-MT-001 — InstitutionScope enforces multi-tenant isolation
    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope());
    }

    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'weights',
        'hot_threshold',
        'warm_threshold',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weights'       => 'array',
            'hot_threshold' => 'integer',
            'warm_threshold' => 'integer',
            'is_active'     => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Default scoring weights — all signal categories, max total = 100.
     * BRD: CRM-LQ-002
     *
     * @return array<string, int>
     */
    public static function defaultWeights(): array
    {
        return [
            'profile_completeness' => 25, // email, city, state, first+last name, nationality
            'programme_interest'   => 20, // has at least one linked programme of interest
            'source_quality'       => 20, // signal weight based on lead source channel
            'engagement'           => 20, // status advancement, counsellor assigned, activity signals
            'consent'              => 5,  // DPDP consent_given = true
            'geographic'           => 5,  // state/location completeness + proximity (stub)
            'response_time'        => 5,  // stub until assigned_at column added in Group E
        ];
    }
}
