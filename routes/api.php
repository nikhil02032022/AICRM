<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CRM\ErpMatchController;
use App\Http\Controllers\Api\CRM\LeadController;
use App\Http\Controllers\Api\CRM\LeadMergeController;
use App\Http\Controllers\Api\CRM\LeadScoringController;
use App\Http\Controllers\Api\CRM\WebFormController;
use App\Http\Controllers\Api\CRM\Webhooks\EducationPortalWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\EmailWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\GoogleLeadWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\IvrWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\MetaLeadWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\SmsGatewayWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\TelephonyWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\WhatsAppWebhookController;
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

        // BRD: CRM-LQ-001, CRM-LQ-005, CRM-LQ-007 — Scoring configuration + manual override
        Route::get('scoring/config', [LeadScoringController::class, 'config'])
            ->name('scoring.config');
        Route::put('scoring/config', [LeadScoringController::class, 'updateConfig'])
            ->name('scoring.config.update');
        Route::post('leads/{lead:uuid}/score-override', [LeadScoringController::class, 'override'])
            ->name('leads.score-override');

        // -----------------------------------------------------------------------
        // Group G — Duplicate Merge + ERP Lead Match
        // BRD: CRM-LC-019, CRM-LC-020
        // -----------------------------------------------------------------------

        // BRD: CRM-LC-019 — Manual lead merge; returns 202 Accepted (async job)
        Route::post('leads/{lead:uuid}/merge', LeadMergeController::class)
            ->name('leads.merge');
        Route::get('leads/{lead:uuid}/merge-status', [LeadMergeController::class, 'status'])
            ->name('leads.merge-status');

        // BRD: CRM-LC-020 — ERP Student Master match check (trigger + query)
        Route::post('leads/{lead:uuid}/check-erp', ErpMatchController::class)
            ->name('leads.check-erp');
        Route::get('leads/{lead:uuid}/erp-match', [ErpMatchController::class, 'show'])
            ->name('leads.erp-match');
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

        // -----------------------------------------------------------------------
        // Group F — Communication Engine webhook receivers
        // No Sanctum auth — verified internally per controller (HMAC / IP allowlist)
        // BRD: CRM-CC-003, CRM-CC-008, CRM-CC-011, CRM-CC-017, CRM-CC-019
        // -----------------------------------------------------------------------

        // F1: Email delivery/open/bounce webhooks (Mailgun, SendGrid, SES)
        Route::post('email/{provider}', EmailWebhookController::class)
            ->name('email');

        // F2: SMS delivery receipt webhooks (MSG91, Textlocal, Kaleyra)
        Route::post('sms/{gateway}', SmsGatewayWebhookController::class)
            ->name('sms');

        // F3: WhatsApp inbound + status updates (Meta Cloud API)
        Route::get('whatsapp', [WhatsAppWebhookController::class, 'verify'])
            ->name('whatsapp.verify');
        Route::post('whatsapp', [WhatsAppWebhookController::class, 'receive'])
            ->name('whatsapp.receive');

        // F4: Telephony call status callbacks (Exotel, Ozonetel, Knowlarity)
        Route::post('telephony/{provider}', TelephonyWebhookController::class)
            ->name('telephony');

        // F4: IVR inbound call → lead auto-creation
        Route::post('ivr/{provider}', IvrWebhookController::class)
            ->name('ivr');
    });
