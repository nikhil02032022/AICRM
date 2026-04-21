<?php

declare(strict_types=1);

namespace App\Models\CRM\Portal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

// BRD: CRM-SP-002 — OTP token record; token_hash is SHA-256 of the 6-digit plain code
final class PortalOtpToken extends Model
{
    protected $table = 'portal_otp_tokens';

    /** @var list<string> */
    protected $fillable = [
        'lead_uuid',
        'institution_id',
        'channel',
        'token_hash',
        'expires_at',
        'used_at',
        'ip_address',
    ];

    /** @var array<string, string> */
    protected $casts = [
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
