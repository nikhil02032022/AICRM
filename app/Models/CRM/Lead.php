<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-LC-011 — Lead entity is the central CRM record for an enquiring prospective student
// BRD: CRM-LC-014 — source is mandatory on every lead record
#[ObservedBy(AuditObserver::class)]
class Lead extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
        static::addGlobalScope(new InstitutionScope());
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
        'city',
        'state',
        'nationality',
        'notes',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            // BRD: NFR-SE-002 — PII encrypted at rest using AES-256 (app key)
            'mobile'           => 'encrypted',
            'email'            => 'encrypted',
            // Enum casts
            'status'           => LeadStatus::class,
            'temperature'      => LeadTemperature::class,
            'source'           => LeadSource::class,
            // JSON
            'source_utm_params' => 'array',
            // Booleans
            'consent_given'    => 'boolean',
            'opt_out'          => 'boolean',
            'call_consent_given' => 'boolean',
            // Dates
            'consent_timestamp'  => 'datetime',
            'opt_out_at'         => 'datetime',
            'pii_anonymised_at'  => 'datetime',
            // BRD: CRM-LC-018 — duplicate flag
            'is_duplicate_suspected' => 'boolean',
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
        return $this->belongsTo(\App\Models\User::class, 'assigned_counsellor_id');
    }

    public function programmeInterests(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\CRM\CrmProgramme::class,
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

    public function canConvertToStudent(): bool
    {
        return $this->status->isConvertible() && $this->erp_student_uuid === null;
    }

    // BRD: CRM-CR-003 — Right to erasure: anonymise PII without deleting aggregate record
    public function anonymisePII(): void
    {
        $this->update([
            'first_name'         => 'ANON',
            'last_name'          => 'ANON',
            'mobile'             => 'ANON_' . $this->id,
            'email'              => null,
            'city'               => null,
            'state'              => null,
            'notes'              => null,
            'consent_ip'         => null,
            'pii_anonymised_at'  => now(),
        ]);
    }
}
