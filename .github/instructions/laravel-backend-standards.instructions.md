---
description: "Use when writing PHP Laravel backend code, creating models, services, repositories, controllers, migrations, jobs, events, or any backend file. Enforces A2A-CRM Laravel conventions: strict types, PHP 8.2 enums/readonly/match, Action/Service/Repository pattern, DTOs, Value Objects, Pipeline, Strategy, service container bindings, multi-tenancy scoping, SoftDeletes, UUID, FormRequest, JsonResource, Horizon queues, Eloquent optimisation, N+1 prevention, cache tags, job idempotency, Pint/Larastan/Pest toolchain."
applyTo: "**/*.php"
---

# A2A-CRM Laravel Backend Standards

## Mandatory PHP Header

Every PHP file must begin with:

```php
<?php

declare(strict_types=1);
```

## Directory Structure

```
app/
├── Services/CRM/{Module}/          # Business logic — never in controllers or models
├── Repositories/CRM/{Module}/      # All DB access
├── Models/CRM/                     # Eloquent models (no business logic)
├── Jobs/CRM/                       # All async operations
├── Events/CRM/                     # Domain events for state changes
├── Listeners/CRM/                  # Event handlers
├── DTOs/CRM/                       # Typed data transfer objects
├── Http/
│   ├── Controllers/CRM/
│   │   ├── Web/                    # Session auth · returns view()/redirect() · routes/web.php
│   │   └── Api/                    # Sanctum auth · returns JsonResource · routes/api.php
│   ├── Requests/CRM/               # FormRequest for all validation
│   └── Resources/CRM/              # JsonResource for API responses ONLY
└── Policies/CRM/                   # RBAC gate policies
```

## Web vs API Controller Separation

Every CRM feature has **two** controller classes — never one controller serving both web and API consumers.

### Web Controller (`Controllers/CRM/Web/`)

```php
// app/Http/Controllers/CRM/Web/LeadController.php
// Route file: routes/web.php under prefix /crm/
// Middleware: auth (session-based)
// Responses: view() · redirect() · back() — NEVER JsonResource or JsonResponse

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreLeadRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Lead\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function index(): View
    {
        Gate::authorize('crm.leads.index');
        return view('crm.leads.index');
    }

    public function show(Lead $lead): View
    {
        Gate::authorize('crm.leads.view', $lead);
        return view('crm.leads.show', compact('lead'));
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        Gate::authorize('crm.leads.create');
        $lead = $this->leadService->create($request->validated(), $request->ip());
        return redirect()->route('crm.leads.show', $lead->uuid)
                         ->with('success', 'Lead created successfully.');
    }
}
```

### API Controller (`Controllers/CRM/Api/`)

```php
// app/Http/Controllers/CRM/Api/LeadController.php
// Route file: routes/api.php under prefix /api/v1/crm/
// Middleware: auth:sanctum
// Responses: JsonResource · JsonResponse — NEVER view() or redirect()
// Consumers: React Native mobile app · A2A ERP · Third-party integrations

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreLeadRequest;
use App\Http\Resources\CRM\LeadResource;
use App\Models\CRM\Lead;
use App\Services\CRM\Lead\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function store(StoreLeadRequest $request): JsonResponse
    {
        Gate::authorize('crm.leads.create');
        $lead = $this->leadService->create($request->validated(), $request->ip());
        return response()->json([
            'success' => true,
            'data'    => new LeadResource($lead),
            'message' => 'Lead created successfully.',
        ], 201);
    }

    public function show(Lead $lead): LeadResource
    {
        Gate::authorize('crm.leads.view', $lead);
        return new LeadResource($lead);
    }
}
```

### Livewire Components — No Controller Needed

Livewire components inject the Service layer directly. No HTTP hop, no API call.

```php
// app/Livewire/CRM/Lead/LeadCreate.php
// Livewire's wire protocol is framework-managed — NOT an API route call
final class LeadCreate extends Component
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function save(): void
    {
        Gate::authorize('crm.leads.create');
        $this->validate();
        $this->leadService->create($this->all(), request()->ip());
        $this->redirectRoute('crm.leads.index');
    }
}
```

### Route Registration

```php
// routes/web.php — CRM web app (session auth)
Route::prefix('crm')->middleware(['auth', 'verified', 'institution.scope'])->group(function (): void {
    Route::resource('leads', \App\Http\Controllers\CRM\Web\LeadController::class);
});

// routes/api.php — external integrations (Sanctum token auth)
Route::prefix('v1/crm')->middleware(['auth:sanctum', 'institution.scope'])->group(function (): void {
    Route::apiResource('leads', \App\Http\Controllers\CRM\Api\LeadController::class);
});
```

## Models

```php
// REQUIRED: every CRM core model
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasUuids, SoftDeletes;

    // InstitutionScope applied in boot()
    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope());
    }

    // Never use fillable = ['*']
    protected $fillable = ['name', 'mobile', 'email', ...specific fields...];

    // Encrypted PII columns
    protected $casts = [
        'mobile' => EncryptedCast::class,
        'email'  => EncryptedCast::class,
    ];
}
```

## Controllers (Thin)

Web controllers return views/redirects. API controllers return JsonResource. They share the same Service.

```php
// Web Controller — routes/web.php, session auth, returns view/redirect
public function store(CreateLeadRequest $request): RedirectResponse
{
    Gate::authorize('crm.leads.create');
    $lead = $this->leadService->create($request->validated(), $request->ip());
    return redirect()->route('crm.leads.show', $lead->uuid)
                     ->with('success', 'Lead created.');
}

// API Controller — routes/api.php, Sanctum auth, returns JsonResource
public function store(CreateLeadRequest $request): JsonResponse
{
    Gate::authorize('crm.leads.create');
    $lead = $this->leadService->create($request->validated(), $request->ip());
    return response()->json(['success' => true, 'data' => new LeadResource($lead)], 201);
}
```

## Services (Business Logic)

```php
// BRD: CRM-LC-018 — Auto-detect duplicate leads on mobile/email
final class LeadService
{
    public function __construct(
        private readonly LeadRepository $repository,
        private readonly DuplicateDetectionService $duplicateDetector,
    ) {}

    public function create(array $validated): Lead
    {
        $this->duplicateDetector->check($validated);
        $lead = $this->repository->create($validated);
        LeadCreatedEvent::dispatch($lead);
        return $lead;
    }
}
```

## Migrations

```php
Schema::create('leads', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();           // exposed externally
    $table->unsignedBigInteger('institution_id')->index();
    $table->unsignedBigInteger('campus_id')->nullable()->index();
    // ...columns...
    $table->timestamps();
    $table->softDeletes();                    // REQUIRED — no hard deletes
});
```

Every migration MUST have a working `down()` method.

## Jobs

```php
// All Anthropic API calls, bulk sends, ERP writes → queue
// NEVER call these synchronously in a web request

class RecalculateLeadScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds

    public function handle(LeadScoringService $service): void
    {
        // Idempotent — safe to retry
    }
}
```

## API Response Format

```php
// Standard success
return response()->json([
    'success' => true,
    'data'    => new LeadResource($lead),
    'message' => 'Lead created successfully.',
    'meta'    => [],
], 201);

// Standard error — never expose stack traces
return response()->json([
    'success' => false,
    'error'   => ['code' => 'LEAD_NOT_FOUND', 'message' => 'Lead not found.'],
], 404);
```

## PHP 8.2+ Mandatory Features

```php
// ✅ Readonly DTOs — immutable, typed, no mutation
final readonly class CreateLeadDTO
{
    public function __construct(
        public string $firstName,
        public string $mobile,
        public string $source,
        public bool   $consentGiven,
        public string $consentIp,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(...$validated);
    }
}

// ✅ Backed Enums — replace ALL string constants
enum LeadTemperature: string
{
    case HOT  = 'HOT'; case WARM = 'WARM';
    case COLD = 'COLD'; case LOST = 'LOST'; case CONVERTED = 'CONVERTED';

    public function isActive(): bool
    {
        return match($this) {
            self::HOT, self::WARM, self::COLD => true,
            default => false,
        };
    }
}

// ✅ Constructor property promotion — no redundant assignment
public function __construct(
    private readonly LeadRepositoryInterface     $leads,
    private readonly DuplicateDetectionService   $duplicates,
    private readonly EventDispatcherInterface    $events,
) {}

// ✅ match over switch — exhaustive, expression-based
$queue = match($priority) {
    JobPriority::CRITICAL => 'crm-critical',
    JobPriority::BULK     => 'crm-bulk',
    default               => 'crm-default',
};
```

## Action vs Service — When to Use Each

| Use | Pattern | Location |
|-----|---------|----------|
| Single user-triggered operation | **Action** | `app/Actions/CRM/` |
| Orchestrating multiple steps/jobs | **Service** | `app/Services/CRM/` |
| Database access for one model | **Repository** | `app/Repositories/CRM/` |
| Sequential transformation stages | **Pipeline** | `app(Pipeline::class)` |
| Swappable algorithm (gateway, SMS) | **Strategy** | Service container binding |
| Model lifecycle side-effects only | **Observer** | Audit log + cache only |

## Repository Interface Binding

```php
// AppServiceProvider or CrmModuleServiceProvider
$this->app->bind(
    LeadRepositoryInterface::class,
    EloquentLeadRepository::class,
);

// Contextual binding for swappable strategies
$this->app->bind(PaymentGatewayInterface::class, function (): PaymentGatewayInterface {
    return match(IntegrationCredentialService::getGateway()) {
        'razorpay' => app(RazorpayGateway::class),
        'payu'     => app(PayUGateway::class),
        default    => app(CCavenueGateway::class),
    };
});
```

## Eloquent — N+1 Prevention

```php
// ❌ N+1 — one query per lead's counsellor
$leads = Lead::paginate(25);
foreach ($leads as $lead) { echo $lead->counsellor->name; }

// ✅ Eager load with column selection — always in repository methods
Lead::select(['uuid', 'first_name', 'lead_score', 'temperature', 'status', 'assigned_counsellor_id'])
    ->with(['counsellor:id,name', 'programmeInterests.programme', 'latestTask'])
    ->paginate(25);

// ✅ Sub-query for "latest of" relationship — zero extra queries
Lead::addSelect([
    'next_task_due' => Task::select('due_at')
        ->whereColumn('lead_id', 'leads.id')
        ->where('status', 'pending')
        ->orderBy('due_at')
        ->limit(1),
])->paginate(25);
```

## Job Reliability — Idempotency + Backoff

```php
final class RecalculateLeadScoreJob implements ShouldQueue, ShouldBeUnique
{
    public int   $tries  = 3;
    public array $backoff = [30, 120, 300]; // exponential seconds

    public function uniqueId(): string { return "lead-score:{$this->leadUuid}"; }

    public function middleware(): array
    {
        return [new RateLimited('ai-scoring')];
    }

    public function handle(LeadScoringService $service): void
    {
        // Always re-fetch — avoid stale serialized state
        $lead = Lead::whereUuid($this->leadUuid)->firstOrFail();
        $service->recalculate($lead);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Score job failed', ['lead_uuid' => $this->leadUuid]); // NO PII
    }
}
```

## Cache Tags + Atomic Locks

```php
// Cache with tags for targeted invalidation
Cache::tags(['crm', "institution:{$id}", 'leads'])
    ->remember("lead_funnel:{$id}", 300, fn() => $this->computeFunnel());

// Invalidate all lead caches for institution after mutation
Cache::tags(["institution:{$id}", 'leads'])->flush();

// Atomic lock to prevent duplicate scheduled job execution
$lock = Cache::lock("cron:priority_list:{$institutionId}", 300);
if ($lock->get()) {
    try { $this->generate(); } finally { $lock->release(); }
}
```

## Code Quality Toolchain

| Tool | Run | Purpose |
|------|-----|---------|
| `./vendor/bin/pint` | CI + pre-commit | Code style (PSR-12 + Laravel preset) |
| `./vendor/bin/phpstan analyse` | CI | Static analysis — Level 8 minimum |
| `php artisan test --coverage --min=70` | CI | Test coverage gate |
| `composer audit` | CI | Dependency vulnerability scan |

```json
// pint.json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "final_class": true,
        "no_unused_imports": true
    }
}
```

## Prohibited Patterns

- ❌ `$request->validate()` in controllers — use FormRequest
- ❌ Raw SQL with string interpolation — use Eloquent bindings
- ❌ `Lead::all()` or `Lead::get()` without institution scope
- ❌ Business logic in `Observer` — observers are for audit log + cache only
- ❌ `fillable = ['*']` on any CRM model
- ❌ Synchronous Anthropic API, email, SMS, or ERP calls in HTTP requests
- ❌ `Log::info()` with PII fields (mobile, email, name, Aadhaar)
- ❌ Credentials in `.env` for third-party services — use `integration_credentials` table (AES-256)
- ❌ String constants where an Enum should be used
- ❌ `array` type hints where a DTO/Value Object should be used
- ❌ Non-`final` service and action classes (prevents accidental inheritance)
- ❌ `withoutGlobalScope(InstitutionScope::class)` in any CRM code path
- ❌ Web controller returning `JsonResource` or `JsonResponse` — use `view()` / `redirect()`
- ❌ API controller returning `view()` or `redirect()` — use `JsonResource` / `JsonResponse`
- ❌ Single controller class registered in both `routes/web.php` and `routes/api.php`
- ❌ Livewire component calling `Http::` client to reach `/api/v1/...` internally
- ❌ `auth:sanctum` middleware on any route in `routes/web.php`

## Advanced Reference

For deeper pattern guidance, load the `/laravel-architecture` skill.
