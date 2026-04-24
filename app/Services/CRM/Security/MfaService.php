<?php

declare(strict_types=1);

namespace App\Services\CRM\Security;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

// NFR-SE-003 — TOTP-based MFA for admin and manager roles.
final class MfaService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * Generate a new MFA secret and recovery codes for a user.
     * Saves the encrypted secret immediately; user must call activateMfa() after verifying first TOTP.
     *
     * @return array{qr_url: string, secret: string, recovery_codes: list<string>}
     */
    public function enableMfa(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $plainCodes = $this->generateRecoveryCodes();
        $hashedCodes = array_map(fn (string $c) => bcrypt($c), $plainCodes);

        $user->google2fa_secret    = $secret;
        $user->mfa_recovery_codes  = $hashedCodes;
        $user->save();

        $qrUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        return [
            'qr_url'         => $qrUrl,
            'secret'         => $secret,
            'recovery_codes' => $plainCodes,
        ];
    }

    /**
     * Verify a TOTP code against the user's stored secret.
     */
    public function verifyTotp(User $user, string $code): bool
    {
        if (! $user->google2fa_secret) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($user->google2fa_secret, $code);
    }

    /**
     * Activate MFA after the user has verified their first TOTP code.
     */
    public function activateMfa(User $user): void
    {
        $user->mfa_enabled    = true;
        $user->mfa_enabled_at = now();
        $user->save();
    }

    /**
     * Disable MFA and clear all MFA-related fields for a user.
     */
    public function disableMfa(User $user): void
    {
        $user->mfa_enabled        = false;
        $user->mfa_enabled_at     = null;
        $user->google2fa_secret   = null;
        $user->mfa_recovery_codes = null;
        $user->save();
    }

    /**
     * Verify a recovery code against the user's stored hashed codes.
     * Invalidates (removes) the used code on success.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $stored = $user->mfa_recovery_codes ?? [];

        foreach ($stored as $i => $hash) {
            if (password_verify($code, $hash)) {
                unset($stored[$i]);
                $user->mfa_recovery_codes = array_values($stored);
                $user->save();

                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function generateRecoveryCodes(): array
    {
        return array_map(fn () => Str::upper(Str::random(4).'-'.Str::random(4)), range(1, 8));
    }
}
