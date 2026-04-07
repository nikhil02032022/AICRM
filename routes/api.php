<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CRM\LeadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| A2A-CRM API Routes — /api/v1/crm/...
|--------------------------------------------------------------------------
|
| All CRM API routes are versioned under /api/v1/crm/.
| Each route group requires: auth:sanctum + tenancy middleware.
|
*/

Route::prefix('v1/crm')
    ->name('api.crm.')
    ->middleware(['auth:sanctum', 'tenancy'])
    ->group(function (): void {
        // Health-check (used in tests)
        Route::get('health-check', fn () => response()->json(['success' => true, 'data' => ['status' => 'ok'], 'message' => 'A2A-CRM is operational']));

        // BRD: CRM-LC-011 — Lead management endpoints
        Route::apiResource('leads', LeadController::class)
            ->parameters(['leads' => 'lead:uuid']);
    });
