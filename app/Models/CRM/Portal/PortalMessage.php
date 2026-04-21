<?php

declare(strict_types=1);

namespace App\Models\CRM\Portal;

use App\Enums\CRM\MessageDirection;
use App\Models\CRM\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-SP-004 — Portal chat message (applicant ↔ counsellor); body encrypted at rest
final class PortalMessage extends Model
{
    protected $table = 'portal_messages';

    /** @var list<string> */
    protected $fillable = [
        'lead_uuid',
        'institution_id',
        'direction',
        'body',
        'sent_by_user_id',
        'applicant_read_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'direction'         => MessageDirection::class,
        'body'              => 'encrypted',
        'applicant_read_at' => 'datetime',
    ];

    /** @return BelongsTo<Lead, PortalMessage> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_uuid', 'uuid');
    }

    public function isFromApplicant(): bool
    {
        return $this->direction === MessageDirection::INBOUND;
    }

    public function isFromCounsellor(): bool
    {
        return $this->direction === MessageDirection::OUTBOUND;
    }

    public function isReadByApplicant(): bool
    {
        return $this->applicant_read_at !== null;
    }
}
