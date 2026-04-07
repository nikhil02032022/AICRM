---
description: "Use when writing unit tests, feature tests, Pest tests, Livewire tests, or Laravel Dusk browser tests for A2A-CRM. Enforces minimum 70% coverage, multi-tenancy test patterns, DPDP compliance test patterns, API contract testing, Livewire component testing, and queue/job testing patterns."
applyTo: ["tests/**/*.php"]
---

# A2A-CRM Testing Standards

## Coverage Requirement

Minimum **70% line coverage** for all CRM modules (BRD: NFR-MT-004).
Run: `php artisan test --coverage --min=70`

## Test Structure (Laravel — Pest PHP)

```php
// tests/Feature/CRM/Lead/CreateLeadTest.php
uses(Tests\TestCase::class)->in('Feature');

describe('LeadService::create', function (): void {

    beforeEach(function (): void {
        // Always set institution context for multi-tenant tests
        $this->institution = Institution::factory()->create();
        $this->actingAs(User::factory()->counsellor()->for($this->institution)->create());
    });

    it('creates a lead with consent captured', function (): void {
        // BRD: CRM-CR-001 — Consent must be recorded
        $payload = CreateLeadData::factory()->withConsent()->make();

        $response = $this->postJson('/api/v1/crm/leads', $payload->toArray());

        $response->assertCreated()
            ->assertJsonStructure(['success', 'data' => ['uuid', 'temperature', 'status']]);

        $this->assertDatabaseHas('leads', [
            'consent_given' => true,
            'institution_id' => $this->institution->id,
        ]);
    });

    it('rejects lead creation without consent', function (): void {
        // BRD: CRM-CR-001 — Consent is mandatory
        $payload = CreateLeadData::factory()->withoutConsent()->make();

        $this->postJson('/api/v1/crm/leads', $payload->toArray())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['consent_given']);
    });

    it('detects duplicate lead on matching mobile', function (): void {
        // BRD: CRM-LC-018 — Duplicate detection
        $existing = Lead::factory()->for($this->institution)->create(['mobile' => '9876543210']);

        $response = $this->postJson('/api/v1/crm/leads', [
            'mobile' => '9876543210',
            'consent_given' => true,
            // ...
        ]);

        $response->assertOk()->assertJsonPath('data.duplicate_detected', true);
    });
});
```

## Multi-Tenancy Test Pattern

CRITICAL: Every feature test must verify institution isolation:

```php
it('cannot see leads from another institution', function (): void {
    $otherInstitution = Institution::factory()->create();
    $otherLead = Lead::factory()->for($otherInstitution)->create();

    // Act as counsellor of *this* institution
    $this->actingAs($this->counsellor);

    $this->getJson("/api/v1/crm/leads/{$otherLead->uuid}")
        ->assertNotFound(); // Must NOT return 403 (which reveals existence)
});
```

## DPDP Compliance Tests

```php
describe('DPDP Act compliance', function (): void {

    it('does not log PII in application logs', function (): void {
        Log::spy();
        $this->postJson('/api/v1/crm/leads', validLeadPayload());
        // Assert no log message contains mobile/email
        Log::shouldNotHaveReceived('info', fn($msg) => str_contains($msg, '9876543210'));
    });

    it('honours opt-out before sending communication', function (): void {
        // BRD: CRM-CR-003
        $lead = Lead::factory()->optedOut()->create();
        $this->expectException(LeadOptedOutException::class);
        app(CommunicationService::class)->send($lead, Channel::EMAIL, $template);
    });

    it('anonymises PII on erasure request', function (): void {
        // BRD: CRM-CR-005
        $lead = Lead::factory()->create(['mobile' => '9999999999']);
        AnonymisePIIJob::dispatchSync($lead->id, 'erasure_request');
        expect($lead->fresh()->mobile)->not->toBe('9999999999');
        expect($lead->fresh()->pii_anonymised_at)->not->toBeNull();
    });
});
```

## Queue/Job Tests

```php
it('dispatches lead scoring job asynchronously on creation', function (): void {
    Queue::fake();

    $this->postJson('/api/v1/crm/leads', validLeadPayload());

    // BRD: AI scoring must NEVER be synchronous
    Queue::assertPushed(RecalculateLeadScoreJob::class);
});

it('dispatches ConvertLeadToStudentJob to queue, not synchronously', function (): void {
    Queue::fake();
    $application = Application::factory()->feeConfirmed()->create();

    $this->postJson("/api/v1/crm/applications/{$application->uuid}/convert-to-student");

    Queue::assertPushed(ConvertLeadToStudentJob::class);
    Queue::assertNotPushedOn('sync', ConvertLeadToStudentJob::class);
});
```

## API Contract Tests

```php
it('returns standard response envelope on success', function (): void {
    $lead = Lead::factory()->for($this->institution)->create();

    $this->getJson("/api/v1/crm/leads/{$lead->uuid}")
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => ['uuid', 'temperature', 'status', 'score', 'source'],
            'message',
            'meta',
        ])
        ->assertJsonPath('success', true)
        ->assertJsonMissing(['id'])  // Internal ID must never be exposed
        ->assertJsonMissing(['institution_id']); // Never expose tenant ID
});
```

## Livewire Component Tests (Pest + Livewire Testing)

```php
// tests/Feature/Livewire/CRM/LeadPipelineTest.php
// BRD: CRM-AP-008 — Kanban pipeline board test
use App\Livewire\CRM\LeadPipeline;
use Livewire\Livewire;

it('renders the lead pipeline with correct lead count', function () {
    $institution = Institution::factory()->create();
    $leads = Lead::factory(5)->for($institution)->withConsent()->create();

    Livewire::actingAs($this->counsellor($institution))
        ->test(LeadPipeline::class)
        ->assertSeeText($leads->first()->first_name)
        ->assertSet('search', '');
});

it('filters leads by temperature on live search', function () {
    $institution = Institution::factory()->create();
    Lead::factory()->for($institution)->hot()->withConsent()->create(['first_name' => 'HotLead']);
    Lead::factory()->for($institution)->cold()->withConsent()->create(['first_name' => 'ColdLead']);

    Livewire::actingAs($this->counsellor($institution))
        ->test(LeadPipeline::class)
        ->set('temperatureFilter', 'HOT')
        ->assertSeeText('HotLead')
        ->assertDontSeeText('ColdLead');
});

it('shows Accept/Edit/Dismiss for AI suggestions (BRD: CRM-AI-011)', function () {
    // AI suggestions must always present human confirmation actions
    $this->get(route('crm.leads.show', $this->lead->uuid))
        ->assertSee('Accept')
        ->assertSee('Edit')
        ->assertSee('Dismiss');
});
```

## Browser Tests (Laravel Dusk — BRD: CRM-AR-004, CRM-AP-008)

```php
// tests/Browser/CRM/LeadKanbanTest.php
use Laravel\Dusk\Browser;

class LeadKanbanTest extends DuskTestCase
{
    public function test_counsellor_can_drag_lead_between_stages(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->counsellor)
                ->visit(route('crm.leads.index'))
                ->assertPresent('[data-testid="pipeline-stage"]')
                ->waitFor('[data-testid="lead-card"]');
        });
    }
}
```

## Test Factories (key patterns)

```php
// database/factories/CRM/LeadFactory.php
class LeadFactory extends Factory
{
    public function withConsent(): static
    {
        return $this->state(['consent_given' => true, 'consent_timestamp' => now()]);
    }

    public function optedOut(): static
    {
        return $this->state(['opt_out' => true, 'opt_out_at' => now()]);
    }

    public function hot(): static
    {
        return $this->state(['temperature' => 'HOT', 'lead_score' => 80]);
    }
}
```

## Prohibited in Tests

- ❌ `RefreshDatabase` in every test — use `DatabaseTransactions` where possible for speed
- ❌ Hard-coded institution IDs (`institution_id = 1`) — use factory-created institutions
- ❌ Skipping multi-tenancy isolation tests
- ❌ Making real HTTP calls to Anthropic/SMS/WhatsApp in tests — always mock/fake
- ❌ `Queue::assertNothingPushed()` as a substitute for actually testing the queue dispatch
