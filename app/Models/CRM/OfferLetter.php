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
        'delivery_status',
        'delivery_message_id',
        // AP-014 conditional offer fields
        'conditional',
        'required_documents',
        'document_verification_status',
        // AP-015 student portal token
        'acceptance_token',
        'acceptance_token_expires_at',
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
            'delivery_status' => 'string',
            'delivery_message_id' => 'string',
            // AP-014 conditional offer fields
            'conditional' => 'boolean',
            'required_documents' => 'array',
            'document_verification_status' => 'array',
            // AP-015 student portal token
            'acceptance_token_expires_at' => 'datetime',
        ];
    }

    /**
     * AP-014: Is this a conditional offer?
     */
    public function isConditional(): bool
    {
        return (bool) $this->conditional;
    }

    /**
     * AP-014: Get required documents for this offer.
     * @return array<string>
     */
    public function getRequiredDocuments(): array
    {
        return $this->required_documents ?? [];
    }

    /**
     * AP-014: Get document verification status (doc_type => bool).
     * @return array<string,bool>
     */
    public function getDocumentVerificationStatus(): array
    {
        return $this->document_verification_status ?? [];
    }

    /**
     * AP-014: Are all required documents verified?
     */
    public function allDocumentsVerified(): bool
    {
        $required = $this->getRequiredDocuments();
        $status = $this->getDocumentVerificationStatus();
        if (empty($required)) {
            return true;
        }
        foreach ($required as $docType) {
            if (empty($status[$docType])) {
                return false;
            }
        }
        return true;
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
        if ($this->isConditional() && ! $this->allDocumentsVerified()) {
            return false;
        }
        return ! $this->isAccepted() && ! $this->isDeclined() && ! $this->isExpired();
    }
}
