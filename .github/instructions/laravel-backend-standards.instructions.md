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
│   ├── Controllers/CRM/            # Thin: validate → service → resource
│   ├── Requests/CRM/               # FormRequest for all validation
│   └── Resources/CRM/              # JsonResource for all API responses
└── Policies/CRM/                   # RBAC gate policies
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

```php
public function store(CreateLeadRequest $request): LeadResource
{
    Gate::authorize('crm.leads.create');
    $lead = $this->leadService->create($request->validated());
    return new LeadResource($lead);
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

## Advanced Reference

For deeper pattern guidance, load the `/laravel-architecture` skill.
