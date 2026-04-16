<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-AP-008, CRM-AP-009 — Application pipeline entity tracks applicant from submission through enrolment
#[ObservedBy(AuditObserver::class)]
class Application extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'applications';

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'lead_uuid',
        'application_form_draft_uuid',
        'admission_cycle_uuid',
        'assigned_counsellor_id',
        'status',
        'stage_entered_at',
        'submitted_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'stage_entered_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_uuid', 'uuid');
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(ApplicationFormDraft::class, 'application_form_draft_uuid', 'uuid');
    }

    public function assignedCounsellor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_counsellor_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'application_uuid', 'uuid');
    }

    public function offerLetters(): HasMany
    {
        return $this->hasMany(OfferLetter::class, 'application_uuid', 'uuid');
    }

    public function conversionLog(): HasMany
    {
        return $this->hasMany(ApplicationConversionLog::class, 'application_uuid', 'uuid');
    }

    /**
     * Get the current offer letter (most recent non-declined).
     */
    public function currentOfferLetter(): HasOne
    {
        return $this->hasOne(OfferLetter::class, 'application_uuid', 'uuid')
            ->ofMany('generated_at', 'max')
            ->whereIn('status', ['pending', 'generated', 'sent', 'accepted'])
            ->latest('generated_at');
    }

    /**
     * Determine if this application can be transitioned to a new status.
     */
    public function canTransitionTo(ApplicationStatus $newStatus): bool
    {
        return in_array($newStatus, $this->status->transitionsTo());
    }
}
