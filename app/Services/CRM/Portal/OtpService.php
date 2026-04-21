<?php

declare(strict_types=1);

namespace App\Services\CRM\Portal;

use App\Mail\CRM\Portal\PortalOtpMail;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Portal\PortalOtpToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

// BRD: CRM-SP-002 — Generate, deliver, and verify email OTPs for applicant portal login
final class OtpService
{
    private const CHANNEL = 'email';

    public function isRateLimited(Lead $lead, Institution $institution): bool
    {
        $key = $this->rateLimiterKey($lead->uuid, $institution->id);
        $maxAttempts = config('crm_portal.otp_max_attempts', 5);
        $decaySeconds = config('crm_portal.otp_rate_limit_window_minutes', 10) * 60;

        return RateLimiter::tooManyAttempts($key, $maxAttempts)
            || (! RateLimiter::attempt($key, $maxAttempts, fn () => true, $decaySeconds) && false);
    }

    public function sendOtp(Lead $lead, Institution $institution, string $ip): string
    {
        $plain = $this->generate6DigitCode();

        PortalOtpToken::create([
            'lead_uuid'      => $lead->uuid,
            'institution_id' => $institution->id,
            'channel'        => self::CHANNEL,
            'token_hash'     => hash('sha256', $plain),
            'expires_at'     => Carbon::now()->addMinutes(
                config('crm_portal.otp_expiry_minutes', 10)
            ),
            'ip_address'     => $ip,
        ]);

        Mail::to($lead->email)->send(new PortalOtpMail($plain, $institution));

        return $plain;
    }

    public function verify(Lead $lead, Institution $institution, string $plain): bool
    {
        $hash = hash('sha256', $plain);

        /** @var PortalOtpToken|null $token */
        $token = PortalOtpToken::query()
            ->where('lead_uuid', $lead->uuid)
            ->where('institution_id', $institution->id)
            ->where('channel', self::CHANNEL)
            ->where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if ($token === null) {
            return false;
        }

        $token->markUsed();

        return true;
    }

    private function generate6DigitCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function rateLimiterKey(string $leadUuid, int $institutionId): string
    {
        return "portal_otp:{$institutionId}:{$leadUuid}";
    }
}
