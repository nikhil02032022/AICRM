<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-DM-007 — Aadhaar eKYC session status lifecycle
enum AadhaarKycStatus: string
{
    case INITIATED  = 'initiated';
    case OTP_SENT   = 'otp_sent';
    case VERIFIED   = 'verified';
    case FAILED     = 'failed';
    case EXPIRED    = 'expired';

    public function label(): string
    {
        return match($this) {
            self::INITIATED => 'Initiated',
            self::OTP_SENT  => 'OTP Sent',
            self::VERIFIED  => 'Verified',
            self::FAILED    => 'Failed',
            self::EXPIRED   => 'Expired',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::INITIATED => 'gray',
            self::OTP_SENT  => 'blue',
            self::VERIFIED  => 'green',
            self::FAILED    => 'red',
            self::EXPIRED   => 'amber',
        };
    }
}
