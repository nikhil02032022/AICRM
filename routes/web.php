<?php

declare(strict_types=1);

use App\Http\Controllers\Web\CRM\LeadWebController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Guest routes
// -----------------------------------------------------------------------
Route::middleware('guest')->group(function (): void {
    Route::get('/', fn () => redirect()->route('login'));

    Route::get('/login', fn () => view('auth.login'))->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    })->name('login.post');
});

// -----------------------------------------------------------------------
// Authenticated routes
// -----------------------------------------------------------------------
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    // BRD: CRM-LC-011 — Lead management web views
    Route::prefix('crm')->name('crm.')->group(function (): void {
        Route::get('/leads', [LeadWebController::class, 'index'])
            ->name('leads.index')
            ->middleware('can:crm.leads.view');
        Route::get('/leads/create', [LeadWebController::class, 'create'])
            ->name('leads.create')
            ->middleware('can:crm.leads.create');
        Route::get('/leads/{lead:uuid}', [LeadWebController::class, 'show'])
            ->name('leads.show')
            ->middleware('can:crm.leads.view');
    });

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
