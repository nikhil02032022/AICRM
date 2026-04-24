<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CRM\Security\MfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// NFR-SE-003 — TOTP MFA setup, verification, and management endpoints.
final class MfaController extends Controller
{
    public function __construct(private readonly MfaService $mfaService) {}

    /**
     * GET /crm/mfa/setup — Show QR code and recovery codes for first-time setup.
     */
    public function setup(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->hasAnyRole(['institution-admin', 'admissions_manager', 'super-admin']), 403);

        $mfaData = $this->mfaService->enableMfa($user);

        return view('crm.auth.mfa.setup', [
            'qr_url'         => $mfaData['qr_url'],
            'secret'         => $mfaData['secret'],
            'recovery_codes' => $mfaData['recovery_codes'],
        ]);
    }

    /**
     * POST /crm/mfa/enable — Confirm first TOTP code and activate MFA.
     */
    public function enable(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $user = $request->user();

        if (! $this->mfaService->verifyTotp($user, $request->input('code'))) {
            return back()->withErrors(['code' => 'The verification code is incorrect. Please try again.']);
        }

        $this->mfaService->activateMfa($user);
        $request->session()->put('mfa_verified', true);

        return redirect()->route('dashboard')->with('success', 'Multi-factor authentication has been enabled.');
    }

    /**
     * GET /crm/mfa/verify — Show TOTP verification form (called during login flow).
     */
    public function showVerify(): View
    {
        return view('crm.auth.mfa.verify');
    }

    /**
     * POST /crm/mfa/verify — Verify TOTP on login and set session flag.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:10']]);

        $user = $request->user();
        $code = $request->input('code');

        // Try TOTP first, then recovery code
        $verified = $this->mfaService->verifyTotp($user, $code)
            || $this->mfaService->verifyRecoveryCode($user, $code);

        if (! $verified) {
            return back()->withErrors(['code' => 'The verification code is incorrect.']);
        }

        $request->session()->put('mfa_verified', true);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * DELETE /crm/mfa/disable/{user} — Admin disables MFA for a user.
     */
    public function disable(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage', $user);

        $this->mfaService->disableMfa($user);

        return back()->with('success', 'MFA has been disabled for '.$user->name.'.');
    }
}
