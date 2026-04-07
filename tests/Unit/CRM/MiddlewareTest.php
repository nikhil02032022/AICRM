<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureInstitutionTenancy;
use App\Http\Middleware\RequireMfa;
use App\Domain\CRM\Models\Institution;
use App\Domain\CRM\Models\Campus;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

uses(RefreshDatabase::class);

// ────────────────────────────────────────────────────────────────────────────
// EnsureInstitutionTenancy middleware
// ────────────────────────────────────────────────────────────────────────────

describe('EnsureInstitutionTenancy middleware', function (): void {

    it('allows through a user with institution_id', function (): void {
        $institution = Institution::create([
            'name' => 'Tenancy Test Uni', 'code' => 'TTU', 'domain' => 'ttu.edu', 'is_active' => true,
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id, 'name' => 'Main', 'code' => 'MAIN', 'city' => 'Pune', 'state' => 'MH', 'is_active' => true,
        ]);

        $user = User::factory()->create([
            'institution_id' => $institution->id,
            'campus_id'      => $campus->id,
        ]);

        $request = Request::create('/crm/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureInstitutionTenancy();
        $response   = $middleware->handle($request, fn () => response('ok', 200));

        expect($response->getStatusCode())->toBe(200);
    });

    it('aborts 403 for a user without institution_id', function (): void {
        $user = User::factory()->create(['institution_id' => null, 'campus_id' => null]);

        $request = Request::create('/crm/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureInstitutionTenancy();

        expect(fn () => $middleware->handle($request, fn () => response('ok', 200)))
            ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    it('aborts 403 for an unauthenticated request', function (): void {
        $request = Request::create('/crm/dashboard', 'GET');
        $request->setUserResolver(fn () => null);

        $middleware = new EnsureInstitutionTenancy();

        expect(fn () => $middleware->handle($request, fn () => response('ok', 200)))
            ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    });
});

// ────────────────────────────────────────────────────────────────────────────
// RequireMfa middleware
// ────────────────────────────────────────────────────────────────────────────

describe('RequireMfa middleware', function (): void {
    beforeEach(function (): void {
        $this->seed([PermissionSeeder::class, RoleSeeder::class, UserSeeder::class]);
    });

    it('allows through an unauthenticated request without MFA check', function (): void {
        $request = Request::create('/crm/dashboard', 'GET');
        $request->setUserResolver(fn () => null);

        $middleware = new RequireMfa();
        $response   = $middleware->handle($request, fn () => response('ok', 200));

        expect($response->getStatusCode())->toBe(200);
    });

    it('allows through an applicant regardless of MFA', function (): void {
        $user = User::where('email', 'applicant@demo.edu')->firstOrFail();
        $user->mfa_enabled = true;

        $session = app('session.store');
        // No mfa_verified in session

        $request = Request::create('/portal/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($session);

        $middleware = new RequireMfa();
        $response   = $middleware->handle($request, fn () => response('ok', 200));

        expect($response->getStatusCode())->toBe(200);
    });

    it('allows through a staff user with MFA disabled', function (): void {
        $user = User::where('email', 'sr.counsellor@demo.edu')->firstOrFail();
        $user->mfa_enabled = false;

        $session = app('session.store');

        $request = Request::create('/crm/leads', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($session);

        $middleware = new RequireMfa();
        $response   = $middleware->handle($request, fn () => response('ok', 200));

        expect($response->getStatusCode())->toBe(200);
    });

    it('allows through a staff user with MFA enabled and session verified', function (): void {
        $user = User::where('email', 'admin@demo.edu')->firstOrFail();
        $user->mfa_enabled = true;

        $session = app('session.store');
        $session->put('mfa_verified', true);

        $request = Request::create('/crm/leads', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($session);

        $middleware = new RequireMfa();
        $response   = $middleware->handle($request, fn () => response('ok', 200));

        expect($response->getStatusCode())->toBe(200);
    });

    it('aborts 403 for a staff user with MFA enabled but not verified in session', function (): void {
        $user = User::where('email', 'admin@demo.edu')->firstOrFail();
        $user->mfa_enabled = true;

        $session = app('session.store');
        // mfa_verified NOT set

        $request = Request::create('/crm/leads', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($session);

        $middleware = new RequireMfa();

        expect(fn () => $middleware->handle($request, fn () => response('ok', 200)))
            ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    });
});
