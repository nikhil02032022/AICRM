<?php

declare(strict_types=1);

use App\Mail\CRM\Portal\PortalOtpMail;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Portal\PortalOtpToken;
use App\Models\CRM\Portal\PortalSession;
use App\Services\CRM\Portal\OtpService;
use App\Services\CRM\Portal\PortalAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Shared setup
// ──────────────────────────────────────────────────────────────

beforeEach(function (): void {
    // Probe routes for PortalAuthenticate middleware tests
    Route::get('/_portal_auth_probe', fn () => response('ok'))
        ->middleware(['portal.branding', 'portal.auth']);
});

function makeInstitutionAndLead(string $domain = 'portal.test'): array
{
    $institution = Institution::factory()->create([
        'domain'    => $domain,
        'is_active' => true,
    ]);

    $lead = Lead::factory()->create([
        'institution_id' => $institution->id,
        'email'          => 'student@' . $domain,
    ]);

    return [$institution, $lead];
}

function institutionParam(Institution $institution): string
{
    return '?institution=' . $institution->uuid;
}

// ──────────────────────────────────────────────────────────────
// SP-002 — Login page
// ──────────────────────────────────────────────────────────────

it('shows the login page on GET /portal/auth/login', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);

    $this->get('/portal/auth/login' . institutionParam($institution))
        ->assertOk()
        ->assertViewIs('portal.auth.login');
});

// ──────────────────────────────────────────────────────────────
// SP-002 — Send OTP
// ──────────────────────────────────────────────────────────────

it('sends an OTP email when a registered email is submitted', function (): void {
    Mail::fake();
    [$institution, $lead] = makeInstitutionAndLead();

    $this->post('/portal/auth/login' . institutionParam($institution), [
        'email' => $lead->email,
    ])->assertRedirect(route('portal.auth.verify-otp'));

    Mail::assertSent(PortalOtpMail::class, function (PortalOtpMail $mail) use ($lead): bool {
        return $mail->hasTo($lead->email);
    });

    expect(PortalOtpToken::where('lead_uuid', $lead->uuid)->count())->toBe(1);
});

it('does not reveal that an email is unregistered (silent redirect)', function (): void {
    Mail::fake();
    $institution = Institution::factory()->create(['is_active' => true]);

    $this->post('/portal/auth/login' . institutionParam($institution), [
        'email' => 'nobody@nowhere.com',
    ])->assertRedirect(route('portal.auth.verify-otp'))
      ->assertSessionHas('info');

    Mail::assertNothingSent();
    expect(PortalOtpToken::count())->toBe(0);
});

it('validates that email is required and well-formed on send-otp', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);

    $this->post('/portal/auth/login' . institutionParam($institution), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

// ──────────────────────────────────────────────────────────────
// SP-002 — Verify OTP
// ──────────────────────────────────────────────────────────────

it('issues a portal session cookie on valid OTP submission', function (): void {
    Mail::fake();
    [$institution, $lead] = makeInstitutionAndLead();

    $plain = app(OtpService::class)->sendOtp($lead, $institution, '127.0.0.1');

    $response = $this->post('/portal/auth/verify' . institutionParam($institution), [
        'email' => $lead->email,
        'otp'   => $plain,
    ]);

    $response->assertRedirect(route('portal.dashboard'));
    expect($response->headers->getCookies())->not->toBeEmpty();
    expect(PortalSession::where('lead_uuid', $lead->uuid)->count())->toBe(1);
});

it('marks the OTP token as used after successful verification', function (): void {
    Mail::fake();
    [$institution, $lead] = makeInstitutionAndLead();
    $plain = app(OtpService::class)->sendOtp($lead, $institution, '127.0.0.1');

    $this->post('/portal/auth/verify' . institutionParam($institution), [
        'email' => $lead->email,
        'otp'   => $plain,
    ]);

    expect(
        PortalOtpToken::where('lead_uuid', $lead->uuid)->whereNotNull('used_at')->count()
    )->toBe(1);
});

it('rejects an invalid OTP code with an error flash', function (): void {
    Mail::fake();
    [$institution, $lead] = makeInstitutionAndLead();
    app(OtpService::class)->sendOtp($lead, $institution, '127.0.0.1');

    $this->post('/portal/auth/verify' . institutionParam($institution), [
        'email' => $lead->email,
        'otp'   => '000000',
    ])->assertSessionHas('error');

    expect(PortalSession::count())->toBe(0);
});

it('rejects a reused OTP on second submission', function (): void {
    Mail::fake();
    [$institution, $lead] = makeInstitutionAndLead();
    $plain = app(OtpService::class)->sendOtp($lead, $institution, '127.0.0.1');

    $this->post('/portal/auth/verify' . institutionParam($institution), [
        'email' => $lead->email,
        'otp'   => $plain,
    ])->assertRedirect();

    $this->post('/portal/auth/verify' . institutionParam($institution), [
        'email' => $lead->email,
        'otp'   => $plain,
    ])->assertSessionHas('error');
});

it('rejects an expired OTP', function (): void {
    [$institution, $lead] = makeInstitutionAndLead();

    PortalOtpToken::create([
        'lead_uuid'      => $lead->uuid,
        'institution_id' => $institution->id,
        'channel'        => 'email',
        'token_hash'     => hash('sha256', '123456'),
        'expires_at'     => Carbon::now()->subMinutes(1),
    ]);

    $this->post('/portal/auth/verify' . institutionParam($institution), [
        'email' => $lead->email,
        'otp'   => '123456',
    ])->assertSessionHas('error');
});

it('validates otp field must be exactly 6 digits', function (): void {
    [$institution] = makeInstitutionAndLead();

    $this->post('/portal/auth/verify' . institutionParam($institution), [
        'email' => 'student@portal.test',
        'otp'   => '12',
    ])->assertSessionHasErrors('otp');
});

// ──────────────────────────────────────────────────────────────
// SP-002 — PortalAuthenticate middleware
// ──────────────────────────────────────────────────────────────

it('PortalAuthenticate passes through with a valid session cookie', function (): void {
    [$institution, $lead] = makeInstitutionAndLead();
    $token = app(PortalAuthService::class)->issueSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/_portal_auth_probe' . institutionParam($institution))
        ->assertOk();
});

it('PortalAuthenticate redirects to login when no cookie is present', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);

    $this->get('/_portal_auth_probe' . institutionParam($institution))
        ->assertRedirect(route('portal.auth.login'));
});

it('PortalAuthenticate rejects an expired session token', function (): void {
    [$institution, $lead] = makeInstitutionAndLead();

    PortalSession::create([
        'lead_uuid'          => $lead->uuid,
        'institution_id'     => $institution->id,
        'session_token_hash' => hash('sha256', 'expiredtoken'),
        'expires_at'         => Carbon::now()->subHour(),
    ]);

    $this->withCookie('portal_session', 'expiredtoken')
        ->get('/_portal_auth_probe' . institutionParam($institution))
        ->assertRedirect(route('portal.auth.login'));
});

// ──────────────────────────────────────────────────────────────
// SP-002 — Logout
// ──────────────────────────────────────────────────────────────

it('logout revokes the portal session and redirects to login', function (): void {
    [$institution, $lead] = makeInstitutionAndLead();
    $token = app(PortalAuthService::class)->issueSession($lead, $institution);

    expect(PortalSession::count())->toBe(1);

    $this->withCookie('portal_session', $token)
        ->post('/portal/auth/logout' . institutionParam($institution))
        ->assertRedirect(route('portal.auth.login'));

    expect(PortalSession::count())->toBe(0);
});

// ──────────────────────────────────────────────────────────────
// SP-002 — Security: tokens must be stored as hashes
// ──────────────────────────────────────────────────────────────

it('stores SHA-256 hash of OTP, not the plain code', function (): void {
    Mail::fake();
    [$institution, $lead] = makeInstitutionAndLead();
    $plain = app(OtpService::class)->sendOtp($lead, $institution, '127.0.0.1');

    $stored = PortalOtpToken::where('lead_uuid', $lead->uuid)->first();

    expect($stored->token_hash)
        ->toBe(hash('sha256', $plain))
        ->not->toBe($plain);
});

it('stores SHA-256 hash of session token, not the plain value', function (): void {
    [$institution, $lead] = makeInstitutionAndLead();
    $plain = app(PortalAuthService::class)->issueSession($lead, $institution);

    $stored = PortalSession::where('lead_uuid', $lead->uuid)->first();

    expect($stored->session_token_hash)
        ->toBe(hash('sha256', $plain))
        ->not->toBe($plain);
});
