<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CRM\LeadController;
use App\Http\Controllers\Api\CRM\WebFormController;
use App\Http\Controllers\Api\CRM\Webhooks\EducationPortalWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\GoogleLeadWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\MetaLeadWebhookController;
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

        // BRD: CRM-LC-001 — WebForm management endpoints (external consumers only)
        Route::apiResource('forms', WebFormController::class)
            ->parameters(['forms' => 'form:uuid']);
        // BRD: CRM-LC-009 — QR code PNG download
        Route::get('forms/{form:uuid}/qr', [WebFormController::class, 'qr'])
            ->name('crm.forms.qr');
    });

// -----------------------------------------------------------------------
// Webhook routes — external platform → CRM
// No auth:sanctum — verified by VerifyWebhookSignature middleware (HMAC-SHA256)
// Throttled to 60 req/min to prevent abuse
// -----------------------------------------------------------------------
Route::prefix('v1/crm/webhooks')
    ->name('api.crm.webhooks.')
    ->middleware(['throttle:60,1'])
    ->group(function (): void {
        // BRD: CRM-LC-003 — Google Lead Form Extensions webhook
        Route::post('google/{integration}', GoogleLeadWebhookController::class)
            ->middleware('crm.webhook:google')
            ->name('google');

        // BRD: CRM-LC-004 — Meta Lead Ads webhook (GET = challenge, POST = lead event)
        Route::get('meta/{integration}', [MetaLeadWebhookController::class, 'verify'])
            ->name('meta.verify');
        Route::post('meta/{integration}', [MetaLeadWebhookController::class, 'receive'])
            ->middleware('crm.webhook:meta')
            ->name('meta.receive');

        // BRD: CRM-LC-008 — Education portal webhooks (Shiksha, CollegeDekho, Careers360, Collegedunia)
        Route::post('portal/{channel}/{integration}', EducationPortalWebhookController::class)
            ->middleware('crm.webhook:portal')
            ->name('portal');
    });
