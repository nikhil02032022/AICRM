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
        // BRD: CRM-LC-011 — Web form POST; session auth, returns JSON for the modal
        Route::post('/leads', [LeadWebController::class, 'store'])
            ->name('leads.store')
            ->middleware('can:crm.leads.create');
        Route::get('/leads/{lead:uuid}', [LeadWebController::class, 'show'])
            ->name('leads.show')
            ->middleware('can:crm.leads.view');
        // BRD: CRM-LC-011 — Web PUT/DELETE; session auth, returns JSON for modals
        Route::put('/leads/{lead:uuid}', [LeadWebController::class, 'update'])
            ->name('leads.update')
            ->middleware('can:crm.leads.edit');
        Route::delete('/leads/{lead:uuid}', [LeadWebController::class, 'destroy'])
            ->name('leads.destroy')
            ->middleware('can:crm.leads.delete');
    });

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
