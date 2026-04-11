<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ErpMatchStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Enums\CRM\LostReason;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

// BRD: CRM-LC-011 — Lead entity is the central CRM record for an enquiring prospective student
// BRD: CRM-LC-014 — source is mandatory on every lead record
#[ObservedBy(AuditObserver::class)]
class Lead extends Model
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $table = 'leads';

    /**
     * HasUuids targets only the 'uuid' column — keeping 'id' as auto-increment bigint.
     * The uuid is the public-facing identifier; id is internal/FK only.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // BRD: NFR-MT-001 — InstitutionScope enforces multi-tenant isolation
    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'first_name',
        'last_name',
        'mobile',
        'email',
        'source',
        'source_utm_params',
        'lead_score',
        'temperature',
        'status',
        'assigned_counsellor_id',
        'agent_id',
        'consent_given',
        'consent_timestamp',
        'consent_ip',
        'consent_form_version',
        'opt_out',
        'opt_out_at',
        'call_consent_given',
        'erp_student_uuid',
        'pii_anonymised_at',
        // BRD: CRM-LC-018 — duplicate detection flag columns
        'is_duplicate_suspected',
        'duplicate_of_uuid',
        // BRD: CRM-LQ-007 — manual score override flag
        'score_manually_overridden',
        'city',
        'state',
        'nationality',
        'notes',
        // BRD: CRM-EC-001 — Academic background fields
        'qualification',
        'marks_10th',
        'board_10th',
        'marks_12th',
        'board_12th',
        'graduation_percentage',
        'graduation_university',
        'preferred_intake',
        'date_of_birth',
        // BRD: CRM-EC-013 — Loss reason (required when status = LOST)
        'lost_reason',
        // BRD: CRM-EC-014 — Timestamp of last status change (used for escalation)
        'status_changed_at',
        // BRD: CRM-CC-005 — Email unsubscribe tracking (DPDP)
        'email_unsubscribed_at',
        'email_bounce_count',
        // BRD: CRM-CC-006 — SMS DNC/opt-out tracking (DPDP)
        'sms_unsubscribed_at',
        'dnc_at',
        'dnc_reason',
        // BRD: CRM-LC-020 — ERP Student Master match status and linked student UUID
        'erp_match_status',
        // BRD: CRM-LC-019 — Merge tombstone (set on secondary lead after merge)
        'merged_into_uuid',
        'merged_at',
        'merge_initiated_by',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            // BRD: NFR-SE-002 — PII encrypted at rest using AES-256 (app key)
            'mobile' => 'encrypted',
            'email' => 'encrypted',
            // Enum casts
            'status' => LeadStatus::class,
            'temperature' => LeadTemperature::class,
            'source' => LeadSource::class,
            // JSON
            'source_utm_params' => 'array',
            // Booleans
            'consent_given' => 'boolean',
            'opt_out' => 'boolean',
            'call_consent_given' => 'boolean',
            // Dates
            'consent_timestamp' => 'datetime',
            'opt_out_at' => 'datetime',
            'pii_anonymised_at' => 'datetime',
            // BRD: CRM-LC-018 — duplicate flag
            'is_duplicate_suspected' => 'boolean',
            // BRD: CRM-LQ-007 — manual override lock flag
            'score_manually_overridden' => 'boolean',
            // BRD: CRM-EC-001 — Academic marks as decimals
            'marks_10th' => 'decimal:2',
            'marks_12th' => 'decimal:2',
            'graduation_percentage' => 'decimal:2',
            'date_of_birth' => 'date',
            // BRD: CRM-EC-013 — Lost reason enum
            'lost_reason' => LostReason::class,
            // BRD: CRM-EC-014 — Last status change timestamp
            'status_changed_at' => 'datetime',
            // BRD: CRM-CC-005 — Email unsubscribe (DPDP)
            'email_unsubscribed_at' => 'datetime',
            'email_bounce_count' => 'integer',
            // BRD: CRM-CC-006 — SMS DNC (DPDP)
            'sms_unsubscribed_at' => 'datetime',
            'dnc_at' => 'datetime',
            // BRD: CRM-LC-020 — ERP match state machine
            'erp_match_status' => ErpMatchStatus::class,
            // BRD: CRM-LC-019 — Merge tombstone datetime
            'merged_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function assignedCounsellor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_counsellor_id');
    }

    public function programmeInterests(): BelongsToMany
    {
        return $this->belongsToMany(
            CrmProgramme::class,
            'lead_programme_interests',
            'lead_id',
            'crm_programme_id',
        )->withPivot('is_primary')->withTimestamps();
    }

    // BRD: CRM-LC-011 — Audit trail for this lead (read-only, append-only)
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'entity_id')
            ->where('entity_type', static::class);
    }

    // BRD: CRM-EC-004 — CRM activity timeline (notes, calls, status changes, comms, etc.)
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest('created_at');
    }

    // BRD: CRM-EC-015 — Counselling sessions for this lead
    public function sessions(): HasMany
    {
        return $this->hasMany(CounsellingSession::class, 'lead_id');
    }

    // BRD: CRM-LC-016 — Attribution timeline entries for this lead.
    public function attributions(): HasMany
    {
        return $this->hasMany(LeadAttribution::class, 'lead_id')->orderBy('touchpoint_at');
    }

    // BRD: CRM-LC-019 — Reverse link: the primary lead this record was merged into
    public function mergedIntoLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'merged_into_uuid', 'uuid');
    }

    // -------------------------------------------------------------------------
    // Domain helpers
    // -------------------------------------------------------------------------

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isAnonymised(): bool
    {
        return $this->pii_anonymised_at !== null;
    }

    // BRD: CRM-LC-019 — True when this lead has been merged into another (tombstone record)
    public function isMerged(): bool
    {
        return $this->merged_into_uuid !== null;
    }

    public function canConvertToStudent(): bool
    {
        return $this->status->isConvertible() && $this->erp_student_uuid === null;
    }

    // BRD: CRM-CR-003 — Right to erasure: anonymise PII without deleting aggregate record
    public function anonymisePII(): void
    {
        $this->update([
            'first_name' => 'ANON',
            'last_name' => 'ANON',
            'mobile' => 'ANON_'.$this->id,
            'email' => null,
            'city' => null,
            'state' => null,
            'notes' => null,
            'consent_ip' => null,
            'pii_anonymised_at' => now(),
        ]);
    }
}
