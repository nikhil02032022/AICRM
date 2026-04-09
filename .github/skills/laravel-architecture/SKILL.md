---
name: laravel-architecture
description: "Senior Laravel solution architecture patterns for A2A-CRM. Use when choosing design patterns, structuring modules, designing service providers, optimising Eloquent, designing job topology, implementing security hardening, or making any backend architecture decision. Trigger: action class, service, repository, pipeline, strategy, DTO, value object, observer, service provider, service container, job chain, job batch, query optimisation, N+1, cache tags, atomic lock, rate limiting, Sanctum, policy, PHP 8.2, enum, readonly, Larastan, Pint, Horizon."
argument-hint: "Architecture challenge to solve (e.g. 'design payment gateway abstraction', 'fix N+1 on lead pipeline list')"
---

# Laravel Architecture — Senior Solution Patterns

## When to Use This Skill

- Choosing the right pattern (Action vs Service vs Repository vs Pipeline)
- Designing service container bindings and service providers
- Preventing N+1, slow queries, and memory issues in Eloquent
- Designing queue topology and job reliability patterns
- Applying PHP 8.2+ language features appropriately
- Security hardening: credential storage, webhook verification, rate limiting
- Setting up code quality toolchain (Pint, Larastan, Pest)

---

## Pattern Selection Decision Tree

```
Is this a single user-triggered operation with one job?
  → Action Class (app/Actions/CRM/)

Is this an orchestration of multiple steps, jobs, or repositories?
  → Service Class (app/Services/CRM/)

Is this database access for a specific model?
  → Repository (app/Repositories/CRM/) + Interface binding

Does the same logic run in sequence, each step passing output forward?
  → Pipeline (app(Pipeline::class)->send()->through()->thenReturn())

Is the algorithm swappable per configuration (gateway, assignment, provider)?
  → Strategy Pattern + Service Container contextual binding

Is this triggered by a model lifecycle event (created/updated/deleted)?
  → Observer — ONLY for audit logging and cache invalidation, not business logic

Is this a domain concept with validation behaviour (phone, email, money)?
  → Value Object (readonly, final, throws on invalid input)

Is this data moving between layers without behaviour?
  → DTO (readonly class, no methods except static factories)

Is this a browser user (counsellor, admin) interacting with the CRM?
  → Web Controller (Controllers/CRM/Web/) in routes/web.php, session auth, returns view/redirect

Is this a machine consumer (mobile app, ERP, third-party)?
  → API Controller (Controllers/CRM/Api/) in routes/api.php, Sanctum token, returns JsonResource
```

---

## Web vs API Controller Separation

This is a hard architectural boundary. Never cross it.

```
┌─────────────────────────────────┬────────────────────────────────────┐
│ Web App (Blade + Livewire)      │ Integration API (external only)    │
├─────────────────────────────────┼────────────────────────────────────┤
│ routes/web.php                  │ routes/api.php                     │
│ /crm/...                        │ /api/v1/crm/...                    │
│ auth middleware (session)        │ auth:sanctum (Bearer token)        │
│ Controllers/CRM/Web/            │ Controllers/CRM/Api/               │
│ Returns: view() redirect()      │ Returns: JsonResource JsonResponse │
│ Consumers: browser users        │ Consumers: mobile, ERP, 3rd-party  │
│ Livewire: injects Service       │ N/A                                │
└─────────────────────────────────┴────────────────────────────────────┘
                        ↑ Both share the same Service layer ↑
```

### Rule: One Service, Two Controllers, Correct Routes

```php
// ✅ Shared Service — business logic belongs here, not in controllers
final class LeadService
{
    public function create(array $validated, string $ip): Lead
    {
        // BRD: CRM-LC-001 — same logic regardless of whether caller is web or API
        $dto = CreateLeadDTO::fromRequest($validated + ['_ip' => $ip]);
        $lead = $this->repository->create($dto);
        LeadCreatedEvent::dispatch($lead);
        return $lead;
    }
}

// ✅ Web Controller — injects Service, returns view/redirect
// Namespace: App\Http\Controllers\CRM\Web
final class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        Gate::authorize('crm.leads.create');
        $lead = $this->leadService->create($request->validated(), $request->ip());
        return redirect()->route('crm.leads.show', $lead->uuid);
    }
}

// ✅ API Controller — same Service injected, returns JsonResource
// Namespace: App\Http\Controllers\CRM\Api
final class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): JsonResponse
    {
        Gate::authorize('crm.leads.create');
        $lead = $this->leadService->create($request->validated(), $request->ip());
        return response()->json(['success' => true, 'data' => new LeadResource($lead)], 201);
    }
}

// ✅ Livewire — injects Service directly (no HTTP hop, no API call)
final class LeadCreate extends Component
{
    public function save(): void
    {
        Gate::authorize('crm.leads.create');
        $this->validate();
        $this->leadService->create($this->all(), request()->ip());
        $this->redirectRoute('crm.leads.index');
    }
}
```

### Web vs API Anti-Patterns to Reject

```php
// ❌ Web controller returning JSON for consumption by Blade JS
public function store(): JsonResponse { return response()->json($lead); }

// ❌ API controller returning Blade view
public function index(): View { return view('crm.leads.index'); }

// ❌ Livewire fetching /api/v1/ internally
public function save(): void {
    Http::withToken(session('api_token'))->post('/api/v1/crm/leads', $this->all());
}

// ❌ fetch() targeting /api/v1/ from Blade JS script block
// fetch('/api/v1/crm/leads', { headers: { Authorization: 'Bearer ...' } })

// ❌ auth:sanctum on web route
Route::post('/crm/leads', [LeadController::class, 'store'])->middleware('auth:sanctum');
```

---

## PHP 8.2+ Mandatory Patterns

### Readonly DTOs — Zero Mutation Risk

```php
final readonly class CreateLeadDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $mobile,
        public string $email,
        public string $source,
        public bool   $consentGiven,
        public string $consentIp,
        public string $consentFormVersion,
    ) {}

    // Static factory from validated request data
    public static function fromRequest(array $validated): self
    {
        return new self(
            firstName:           $validated['first_name'],
            lastName:            $validated['last_name'],
            mobile:              $validated['mobile'],
            email:               $validated['email'] ?? '',
            source:              $validated['source'],
            consentGiven:        (bool) $validated['consent_given'],
            consentIp:           $validated['_ip'],
            consentFormVersion:  $validated['consent_form_version'],
        );
    }
}
```

### Backed Enums — Replace All String Constants

```php
// app/Enums/CRM/LeadStatus.php
enum LeadStatus: string
{
    case NEW_ENQUIRY           = 'new_enquiry';
    case CONTACTED             = 'contacted';
    case COUNSELLING_SCHEDULED = 'counselling_scheduled';
    case COUNSELLING_DONE      = 'counselling_done';
    case APPLICATION_STARTED   = 'application_started';
    case APPLICATION_SUBMITTED = 'application_submitted';
    case OFFER_ISSUED          = 'offer_issued';
    case FEE_PAID              = 'fee_paid';
    case ENROLLED              = 'enrolled';
    case DEFERRED              = 'deferred';
    case LOST                  = 'lost';

    public function isConvertible(): bool
    {
        return $this === self::FEE_PAID;
    }

    public function canTransitionTo(self $next): bool
    {
        // Define allowed transitions
        return in_array($next, $this->allowedTransitions(), true);
    }

    /** @return LeadStatus[] */
    private function allowedTransitions(): array
    {
        return match($this) {
            self::NEW_ENQUIRY           => [self::CONTACTED, self::LOST],
            self::CONTACTED             => [self::COUNSELLING_SCHEDULED, self::LOST],
            self::COUNSELLING_SCHEDULED => [self::COUNSELLING_DONE, self::CONTACTED, self::LOST],
            self::COUNSELLING_DONE      => [self::APPLICATION_STARTED, self::LOST],
            self::APPLICATION_STARTED   => [self::APPLICATION_SUBMITTED, self::LOST],
            self::APPLICATION_SUBMITTED => [self::OFFER_ISSUED, self::LOST],
            self::OFFER_ISSUED          => [self::FEE_PAID, self::LOST],
            self::FEE_PAID              => [self::ENROLLED, self::LOST],
            default                     => [],
        };
    }
}
```

---

## Service Provider Structure

```php
// app/Providers/CRM/CrmModuleServiceProvider.php
// Registered in bootstrap/providers.php
final class CrmModuleServiceProvider extends ServiceProvider
{
    public array $bindings = [
        // Simple interface → implementation bindings
        LeadRepositoryInterface::class        => EloquentLeadRepository::class,
        ApplicationRepositoryInterface::class => EloquentApplicationRepository::class,
        DocumentRepositoryInterface::class    => EloquentDocumentRepository::class,
    ];

    public function register(): void
    {
        // Context-dependent binding: payment gateway resolved at runtime
        $this->app->bind(PaymentGatewayInterface::class, function (): PaymentGatewayInterface {
            $gateway = IntegrationCredentialService::getGateway(
                auth()->user()?->institution_id
            );
            return match($gateway) {
                'razorpay' => app(RazorpayGateway::class),
                'payu'     => app(PayUGateway::class),
                'ccavenue' => app(CCavenueGateway::class),
                default    => throw new UnsupportedGatewayException($gateway),
            };
        });

        // Counsellor assignment strategy
        $this->app->bind(CounsellorAssignmentStrategy::class, function (): CounsellorAssignmentStrategy {
            $strategy = InstitutionConfig::get('assignment_strategy', 'round_robin');
            return app(AssignmentStrategyRegistry::class)->resolve($strategy);
        });

        // Communication channel driver
        $this->app->bind(SmsDriverInterface::class, function (): SmsDriverInterface {
            $provider = IntegrationCredentialService::getProvider('sms');
            return match($provider) {
                'msg91'     => app(Msg91Driver::class),
                'textlocal' => app(TextlocalDriver::class),
                'kaleyra'   => app(KaleyraDriver::class),
            };
        });
    }

    public function boot(): void
    {
        Lead::observe(LeadObserver::class);
        Application::observe(ApplicationObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
```

---

## Eloquent — Senior Optimisation Patterns

### Reference: [laravel-backend-standards](../../instructions/laravel-backend-standards.instructions.md)

### Global Scopes — Multi-Tenancy Enforcement

```php
// app/Models/Scopes/InstitutionScope.php
final class InstitutionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check() && auth()->user()->institution_id) {
            $builder->where(
                $model->getTable() . '.institution_id',
                auth()->user()->institution_id
            );
        }
    }
}
```

### Preventing Over-Fetching — Column Selection

```php
// ❌ Loads all columns including encrypted PII into memory
Lead::with('counsellor')->paginate(25);

// ✅ Select only what the list view needs
Lead::select(['uuid', 'first_name', 'lead_score', 'temperature', 'status',
              'assigned_counsellor_id', 'created_at'])
    ->with(['counsellor:id,name'])
    ->paginate(25);
```

### Sub-query Latest Relationship (Avoids N+1)

```php
// Get lead list with latest task due date — NO N+1
Lead::addSelect([
    'next_task_due' => Task::select('due_at')
        ->whereColumn('lead_id', 'leads.id')
        ->where('status', 'pending')
        ->orderBy('due_at')
        ->limit(1),
])->paginate(25);
```

---

## Queue Reliability Patterns

### Job Idempotency Guard

```php
// Every job that writes to external systems must be idempotent
final class ConvertLeadToStudentJob implements ShouldQueue, ShouldBeUnique
{
    public function uniqueId(): string
    {
        return "convert-lead:{$this->applicationUuid}";
    }

    public function handle(StudentMasterConversionService $service): void
    {
        $application = Application::whereUuid($this->applicationUuid)->firstOrFail();

        // Idempotency: skip if already converted
        if ($application->erp_student_uuid !== null) {
            Log::info('Skipping conversion — already converted', [
                'application_uuid' => $this->applicationUuid,
            ]);
            return;
        }

        $service->convert($application);
    }
}
```

### Horizon Supervisor Configuration

```php
// config/horizon.php — queue worker topology for A2A-CRM
'environments' => [
    'production' => [
        'supervisor-critical' => [
            'connection' => 'redis',
            'queue'      => ['crm-critical'],
            'processes'  => 5,
            'tries'      => 3,
            'timeout'    => 30,
            'balance'    => 'auto',
        ],
        'supervisor-ai' => [
            'queue'    => ['crm-ai'],
            'processes' => 3,
            'tries'    => 3,
            'timeout'  => 120,  // AI calls can be slow
        ],
        'supervisor-bulk' => [
            'queue'    => ['crm-bulk'],
            'processes' => 10,
            'tries'    => 2,
            'timeout'  => 300,
            'balance'  => 'auto',
            'maxProcesses' => 20,
        ],
        'supervisor-default' => [
            'queue'    => ['crm-default', 'crm-erp-sync', 'crm-reports'],
            'processes' => 8,
            'balance'  => 'auto',
        ],
    ],
],
```

---

## Security Hardening Checklist

### Middleware Stack for CRM Routes

```php
// routes/api.php — layered middleware
Route::prefix('v1/crm')
    ->middleware([
        'auth:sanctum',          // authentication
        'verified',              // email verified
        'institution.scope',     // inject institution context
        'throttle:crm-authenticated', // rate limiting
        'audit.log',             // write audit entry on mutations
    ])
    ->group(function (): void { ... });
```

### Policy Class — Every Model Needs One

```php
// app/Policies/CRM/LeadPolicy.php
final class LeadPolicy
{
    public function view(User $user, Lead $lead): bool
    {
        // Institution scope enforced by DB — this checks role
        return match($user->crm_role) {
            CrmRole::INSTITUTION_ADMIN,
            CrmRole::ADMISSIONS_HEAD    => true,
            CrmRole::ADMISSIONS_MANAGER => $lead->campus_id === $user->campus_id,
            CrmRole::COUNSELLOR_SENIOR,
            CrmRole::COUNSELLOR_JUNIOR  => $lead->assigned_counsellor_id === $user->id,
            default                     => false,
        };
    }

    public function convert(User $user, Application $application): bool
    {
        // Only managers and above can trigger ERP conversion
        return in_array($user->crm_role, [
            CrmRole::ADMISSIONS_MANAGER,
            CrmRole::ADMISSIONS_HEAD,
            CrmRole::INSTITUTION_ADMIN,
        ], true);
    }
}
```

---

## Code Quality Gates (CI Pipeline)

```yaml
# .github/workflows/backend-quality.yml (reference)
# These checks must pass before any merge:

1. php artisan test --coverage --min=70
2. ./vendor/bin/pint --test               # code style check
3. ./vendor/bin/phpstan analyse            # static analysis level 8
4. composer audit                          # dependency vulnerabilities
5. php artisan route:list                  # validate no broken routes
```

---

## Architectural Red Lines — Never Cross These

| Red Line | Why |
|----------|-----|
| Business logic in Controllers | Untestable, violates SRP |
| Business logic in Model `boot()` | Hidden side effects, untestable |
| `Lead::all()` without scope | Data leak across institutions |
| Sync Anthropic / SMS / ERP calls in HTTP | Blocks web workers, 500ms SLA broken |
| `fillable = ['*']` | Mass assignment vulnerability |
| Raw SQL with string interpolation | SQL injection (OWASP A03) |
| Hard-coded credentials in `.env` | Exposed in server config dumps |
| Observer for business logic | Invisible control flow, debugging nightmare |
| `withoutGlobalScope(InstitutionScope::class)` in CRM code | Multi-tenancy breach |
| `Log::info()` with PII | DPDP Act violation |
