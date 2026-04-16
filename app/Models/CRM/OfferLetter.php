<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-AP-012, CRM-AP-013, CRM-AP-014, CRM-AP-015 — Offer letter entity tracks admission offers and acceptance
#[ObservedBy(AuditObserver::class)]
class OfferLetter extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'offer_letters';

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
        'application_uuid',
        'lead_uuid',
        'programme_uuid',
        'status',
        'generated_at',
        'sent_at',
        'sent_via',
        'acceptance_recorded_at',
        'acceptance_ip',
        'declined_at',
        'decline_reason',
        'expires_at',
        'pdf_path',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'sent_at' => 'datetime',
            'acceptance_recorded_at' => 'datetime',
            'declined_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_uuid', 'uuid');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_uuid', 'uuid');
    }

    /**
     * Determine if this offer has been accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted' && $this->acceptance_recorded_at !== null;
    }

    /**
     * Determine if this offer has been declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === 'declined' && $this->declined_at !== null;
    }

    /**
     * Determine if this offer has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Determine if this offer is still valid for acceptance.
     */
    public function isValidForAcceptance(): bool
    {
        return ! $this->isAccepted() && ! $this->isDeclined() && ! $this->isExpired();
    }
}
