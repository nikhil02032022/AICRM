<?php

declare(strict_types=1);

namespace App\Models\CRM;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ConsentRecord — DPDP Act 2023 § 6 compliant consent storage.
 *
 * BRD: CRM-CR-001 — Explicit consent captured at point of lead creation.
 * BRD: CRM-CR-002 — Stored with timestamp, IP address, form version.
 *
 * This record must be created BEFORE (or simultaneously with) the Lead record.
 * It is never deleted — only the Lead may be anonymised; consent records are
 * retained for the statutory period.
 *
 * @property int $id
 * @property int|null $lead_id Null until lead is persisted
 * @property int $institution_id
 * @property bool $consent_given
 * @property Carbon $consent_timestamp
 * @property string $consent_ip
 * @property string $consent_form_version
 * @property string $consent_channel web_form|api|import|telephony
 * @property string|null $consent_text_snapshot
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ConsentRecord extends Model
{
    protected $table = 'consent_records';

    protected $fillable = [
        'lead_id',
        'institution_id',
        'consent_given',
        'consent_timestamp',
        'consent_ip',
        'consent_form_version',
        'consent_channel',
        'consent_text_snapshot',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
        'consent_timestamp' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    /** Only records where consent was actually given. */
    public function scopeGiven(Builder $query): Builder
    {
        return $query->where('consent_given', true);
    }

    /** Filter to a specific institution. */
    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    /** Filter by channel. */
    public function scopeViaChannel(Builder $query, string $channel): Builder
    {
        return $query->where('consent_channel', $channel);
    }

    // ── Factory helper ───────────────────────────────────────────────────

    /**
     * Build the minimal required attributes for a valid consent record.
     * Use in lead creation services to ensure DPDP compliance.
     *
     * BRD: CRM-CR-001
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function requiredAttributes(
        int $institutionId,
        string $ip,
        string $formVersion,
        string $channel = 'web_form',
        array $overrides = [],
    ): array {
        return array_merge([
            'institution_id' => $institutionId,
            'consent_given' => true,
            'consent_timestamp' => now(),
            'consent_ip' => $ip,
            'consent_form_version' => $formVersion,
            'consent_channel' => $channel,
        ], $overrides);
    }
}
