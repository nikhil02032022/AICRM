<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\AgentPortal;

use App\Http\Controllers\Controller;
use App\Models\CRM\Institution;
use App\Services\CRM\Agents\AgentAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AG-003 — Agent portal email+password authentication
final class AgentPortalAuthController extends Controller
{
    public function __construct(private readonly AgentAuthService $authService) {}

    public function showLogin(): View
    {
        return view('agent-portal.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'           => ['required', 'email'],
            'password'        => ['required', 'string'],
            'institution_code' => ['required', 'string'],
        ]);

        // Resolve institution by short code or ID
        $institution = Institution::withoutGlobalScopes()
            ->where('code', $validated['institution_code'])
            ->orWhere('id', $validated['institution_code'])
            ->first();

        if ($institution === null) {
            return back()->withErrors(['institution_code' => 'Institution not found.']);
        }

        $result = $this->authService->login(
            $validated['email'],
            $validated['password'],
            $institution->id,
            $request->ip(),
            $request->userAgent(),
        );

        if ($result === null) {
            return back()
                ->withInput($request->only('email', 'institution_code'))
                ->withErrors(['email' => 'Invalid credentials or inactive account.']);
        }

        [, $plain] = $result;

        return redirect()
            ->route('agent-portal.dashboard')
            ->withCookie(cookie('agent_portal_session', $plain, 60 * 8, '/', null, true, true));
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = $request->cookie('agent_portal_session');

        if ($token) {
            $this->authService->logout($token);
        }

        return redirect()
            ->route('agent-portal.login')
            ->withCookie(cookie()->forget('agent_portal_session'));
    }
}
