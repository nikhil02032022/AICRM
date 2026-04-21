<?php

declare(strict_types=1);

namespace App\Models\CRM\Portal;

use App\Models\CRM\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-SP-002 — Stateless portal session; session_token_hash is SHA-256 of the bearer cookie value
final class PortalSession extends Model
{
    protected $table = 'portal_sessions';

    /** @var list<string> */
    protected $fillable = [
        'lead_uuid',
        'institution_id',
        'session_token_hash',
        'expires_at',
        'device_fingerprint',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /** @return BelongsTo<Lead, PortalSession> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_uuid', 'uuid');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
