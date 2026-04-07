---
description: "Use when designing or implementing REST API endpoints, API controllers, response structures, authentication, pagination, error handling, or reviewing API contracts for A2A-CRM. Enforces versioned routes, standard response envelope, Sanctum auth, RBAC gate checks, and OWASP API security."
applyTo: "routes/api*.php"
---

# A2A-CRM API Design Standards

## Route Structure

```php
// All CRM routes versioned under /api/v1/crm/
// routes/api/v1/crm.php

Route::prefix('v1/crm')
    ->middleware(['auth:sanctum', 'verified', 'institution.scope'])
    ->group(function (): void {

        // Leads
        Route::apiResource('leads', LeadController::class);
        Route::post('leads/{lead}/convert-to-student', [LeadController::class, 'convertToStudent']);
        Route::post('leads/{lead}/ai-draft', [LeadController::class, 'aiDraft']);

        // Applications
        Route::apiResource('applications', ApplicationController::class);
    });
```

**Always use UUIDs in route parameters** — never expose auto-increment IDs. The `{lead}` route model binding must bind on `uuid` column.

## Standard Response Envelope

```php
// Success
{
    "success": true,
    "data": { ... },           // LeadResource or collection
    "message": "Lead created successfully.",
    "meta": {
        "current_page": 1,
        "per_page": 25,
        "total": 487,
        "last_page": 20
    }
}

// Error
{
    "success": false,
    "error": {
        "code": "LEAD_NOT_FOUND",       // machine-readable constant
        "message": "Lead not found.",   // human-readable
        "field": "uuid"                 // optional, for validation errors
    }
}
```

Never return Eloquent exceptions, stack traces, or query errors to API consumers.

## Authentication & RBAC

```php
// Every controller action must authorize before acting
public function show(Lead $lead): LeadResource
{
    Gate::authorize('crm.leads.view', $lead);
    return new LeadResource($lead);
}

public function convertToStudent(ConversionRequest $request, Application $application): JsonResponse
{
    Gate::authorize('crm.applications.convert', $application);
    // ...
}
```

## JsonResource Pattern

```php
// Never return raw model serialization
// Always use JsonResource
class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'          => $this->uuid,           // NOT $this->id
            'temperature'   => $this->temperature->value,
            'status'        => $this->status->value,
            'score'         => $this->lead_score,
            'source'        => $this->source,
            'assigned_to'   => new CounsellorResource($this->whenLoaded('counsellor')),
            'programme_interests' => ProgrammeInterestResource::collection(
                $this->whenLoaded('programmeInterests')
            ),
            'created_at'    => $this->created_at->toISOString(),
            // NEVER expose: id, institution_id, raw mobile/email without role check
        ];
    }
}
```

## Pagination

All list endpoints paginate. Default: 25 per page. Maximum: 100 per page.

```php
public function index(IndexLeadRequest $request): AnonymousResourceCollection
{
    Gate::authorize('crm.leads.index');
    $leads = $this->leadRepository->paginateForUser(
        Auth::user(),
        $request->validated('per_page', 25)
    );
    return LeadResource::collection($leads);
}
```

## Webhook Endpoints (Communication/ERP/Payment callbacks)

```php
// Webhook routes — no auth:sanctum, but signature-verified
Route::prefix('v1/crm/webhooks')
    ->middleware(['webhook.signature'])   // custom middleware verifies HMAC
    ->group(function (): void {
        Route::post('whatsapp/{provider}', [WhatsAppWebhookController::class, 'handle']);
        Route::post('payment/{gateway}',   [PaymentWebhookController::class, 'handle']);
        Route::post('erp/sync',            [ErpWebhookController::class, 'handle']);
    });

// Always process webhooks via queued jobs — never synchronously
public function handle(Request $request, string $provider): JsonResponse
{
    InboundWhatsAppWebhookJob::dispatch($provider, $request->all());
    return response()->json(['success' => true], 202);
}
```

## Rate Limiting

```php
// All public-facing endpoints (lead capture forms, student portal OTP)
RateLimiter::for('crm-public', function (Request $request): Limit {
    return Limit::perMinute(10)->by($request->ip());
});

// Authenticated counsellor endpoints
RateLimiter::for('crm-authenticated', function (Request $request): Limit {
    return Limit::perMinute(120)->by(Auth::id());
});

// AI draft endpoints — throttled to prevent cost abuse
RateLimiter::for('crm-ai', function (Request $request): Limit {
    return Limit::perHour(50)->by(Auth::id());
});
```

## API Versioning Strategy

- Current version: `v1`
- Breaking changes require `v2`
- `v1` maintained for minimum 6 months after `v2` release
- Deprecation notices in response headers: `X-API-Deprecated: true`

## Security Headers

Ensure API responses include:
- `Content-Type: application/json`
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- Never return `Access-Control-Allow-Origin: *` for authenticated endpoints

## Prohibited Patterns

- ❌ Exposing Eloquent model `id` in API responses
- ❌ `->all()` without pagination on any list endpoint
- ❌ Returning exceptions or stack traces in production responses
- ❌ Skipping `Gate::authorize()` on any write endpoint
- ❌ Webhook handlers that process synchronously (always dispatch job)
- ❌ Raw `response()->json($model->toArray())` — use JsonResource
- ❌ Accepting file uploads via JSON API body — use multipart/form-data with S3 direct upload
