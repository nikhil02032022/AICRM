<?php

declare(strict_types=1);

namespace App\Models\CRM\Portal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

// BRD: CRM-SP-007 — Single-use 5-minute token for seamless ERP portal SSO on enrolment
final class ErpBridgeToken extends Model
{
    protected $table = 'erp_bridge_tokens';

    /** @var list<string> */
    protected $fillable = [
        'lead_uuid',
        'institution_id',
        'application_uuid',
        'token_hash',
        'issued_at',
        'expires_at',
        'used_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'issued_at'  => 'datetime',
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isUsed() && ! $this->isExpired();
    }

    public function markUsed(): void
    {
        $this->used_at = Carbon::now();
        $this->save();
    }
}
