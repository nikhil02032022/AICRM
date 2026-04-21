<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Portal;

use App\Http\Controllers\Controller;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Portal\OtpService;
use App\Services\CRM\Portal\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SP-002 — Email-OTP login flow for the applicant self-service portal
final class PortalAuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly PortalAuthService $authService,
    ) {}

    public function showLogin(): View
    {
        return view('portal.auth.login');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        /** @var Institution|null $institution */
        $institution = $request->attributes->get('portal_institution');

        if ($institution === null) {
            return back()->with('error', 'Could not identify your institution. Please use the login link provided by your institution.')->withInput();
        }

        // email is encrypted at rest — must filter in PHP after fetching by institution
        $inputEmail = strtolower(trim($request->input('email')));
        $lead = Lead::withoutGlobalScopes()
            ->where('institution_id', $institution->id)
            ->whereNull('deleted_at')
            ->get()
            ->first(fn (Lead $l) => strtolower(trim($l->email ?? '')) === $inputEmail);

        $verifyUrl = route('portal.auth.verify-otp', ['institution' => $institution->uuid]);

        if ($lead === null) {
            // Deliberate vagueness — do not reveal whether an email is registered
            return redirect()->to($verifyUrl)
                ->with('info', 'If that email is registered, a login code has been sent.')
                ->withInput(['email' => $request->input('email')]);
        }

        if ($this->otpService->isRateLimited($lead, $institution)) {
            return back()->with('error', 'Too many requests. Please wait a few minutes and try again.')->withInput();
        }

        $this->otpService->sendOtp($lead, $institution, $request->ip() ?? '');

        return redirect()->to($verifyUrl)
            ->with('info', 'A 6-digit login code has been sent to your email.')
            ->withInput(['email' => $request->input('email')]);
    }

    public function showVerifyOtp(Request $request): View
    {
        return view('portal.auth.verify-otp', [
            'email' => old('email', $request->query('email', '')),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp'   => ['required', 'digits:6'],
        ]);

        /** @var Institution|null $institution */
        $institution = $request->attributes->get('portal_institution');

        if ($institution === null) {
            return back()->with('error', 'Could not identify your institution. Please use the login link provided by your institution.')->withInput();
        }

        $inputEmail = strtolower(trim($request->input('email')));
        $lead = Lead::withoutGlobalScopes()
            ->where('institution_id', $institution->id)
            ->whereNull('deleted_at')
            ->get()
            ->first(fn (Lead $l) => strtolower(trim($l->email ?? '')) === $inputEmail);

        if ($lead === null || ! $this->otpService->verify($lead, $institution, $request->input('otp'))) {
            return back()
                ->with('error', 'Invalid or expired code. Please try again.')
                ->withInput(['email' => $request->input('email')]);
        }

        $sessionToken = $this->authService->issueSession(
            $lead,
            $institution,
            substr($request->userAgent() ?? '', 0, 255),
        );

        return redirect()->route('portal.dashboard')
            ->withCookie(
                cookie(
                    name: 'portal_session',
                    value: $sessionToken,
                    minutes: config('crm_portal.session_lifetime_hours', 8) * 60,
                    secure: true,
                    httpOnly: true,
                    sameSite: 'Lax',
                )
            );
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = $request->cookie('portal_session');

        if ($token) {
            $this->authService->revokeSession($token);
        }

        return redirect()->route('portal.auth.login')
            ->with('success', 'You have been logged out.')
            ->withCookie(cookie()->forget('portal_session'));
    }
}
