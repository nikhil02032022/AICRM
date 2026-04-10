<?php

declare(strict_types=1);

// BRD: CRM-CC-001 to CRM-CC-005 — Email communication: templates, campaigns, delivery, bounce, unsubscribe

use App\Enums\CRM\CampaignStatus;
use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageStatus;
use App\Enums\CRM\TemplateType;
use App\Events\CRM\Communication\EmailBouncedEvent;
use App\Events\CRM\Communication\EmailSentEvent;
use App\Events\CRM\Communication\EmailUnsubscribedEvent;
use App\Jobs\CRM\Communication\EnforceUnsubscribeJob;
use App\Jobs\CRM\Communication\ProcessEmailWebhookJob;
use App\Jobs\CRM\Communication\SendBulkEmailJob;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\CommunicationTemplate;
use App\Models\CRM\EmailCampaign;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\SenderDomain;
use App\Models\User;
use App\Services\CRM\Communication\EmailService;
use App\Services\CRM\Communication\TemplateService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->institution = Institution::create([
        'name' => 'Email Test University', 'code' => 'ETU1', 'is_active' => true,
    ]);

    $this->counsellor = User::create([
        'name'           => 'Email Counsellor',
        'email'          => 'email-counsellor@etu.com',
        'password'       => bcrypt('password'),
        'institution_id' => $this->institution->id,
    ]);
    $this->counsellor->givePermissionTo(['crm.communication.send', 'crm.leads.view']);

    $this->lead = Lead::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'name'           => 'Test Student',
        'email'          => 'student@example.com',
        'mobile'         => '9876543210',
        'consent_given'  => true,
    ]);
});

// ─── Template CRUD ───────────────────────────────────────────────────────

it('can create a communication template via web', function (): void {
    $this->actingAs($this->counsellor)
        ->post(route('crm.communication.templates.store'), [
            'name'     => 'Welcome Email',
            'channel'  => CommunicationChannel::EMAIL->value,
            'type'     => TemplateType::TRANSACTIONAL->value,
            'subject'  => 'Welcome to {{institution}}',
            'body'     => 'Dear {{name}}, welcome!',
            'language' => 'en',
        ])
        ->assertRedirectContains('templates');

    expect(CommunicationTemplate::where('name', 'Welcome Email')->exists())->toBeTrue();
});

it('stores merge tags in template body', function (): void {
    $template = CommunicationTemplate::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'name'           => 'Merge Test',
        'channel'        => CommunicationChannel::EMAIL,
        'type'           => TemplateType::TRANSACTIONAL,
        'body'           => 'Hello {{name}}, your programme is {{programme}}.',
        'language'       => 'en',
    ]);

    expect($template->body)->toContain('{{name}}');
});

it('can list communication templates', function (): void {
    CommunicationTemplate::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'name'           => 'List Template',
        'channel'        => CommunicationChannel::EMAIL,
        'type'           => TemplateType::TRANSACTIONAL,
        'body'           => 'Body text',
        'language'       => 'en',
    ]);

    $this->actingAs($this->counsellor)
        ->get(route('crm.communication.templates.index'))
        ->assertOk()
        ->assertSee('List Template');
});

it('can update a template', function (): void {
    $template = CommunicationTemplate::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'name'           => 'Original Name',
        'channel'        => CommunicationChannel::EMAIL,
        'type'           => TemplateType::TRANSACTIONAL,
        'body'           => 'Old body',
        'language'       => 'en',
    ]);

    $this->actingAs($this->counsellor)
        ->patch(route('crm.communication.templates.update', $template->uuid), [
            'name'    => 'Updated Name',
            'channel' => CommunicationChannel::EMAIL->value,
            'type'    => TemplateType::TRANSACTIONAL->value,
            'body'    => 'New body content',
        ]);

    expect($template->fresh()->name)->toBe('Updated Name');
});

it('can soft-delete a template', function (): void {
    $template = CommunicationTemplate::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'name'           => 'To Delete',
        'channel'        => CommunicationChannel::EMAIL,
        'type'           => TemplateType::TRANSACTIONAL,
        'body'           => 'Body',
        'language'       => 'en',
    ]);

    $this->actingAs($this->counsellor)
        ->delete(route('crm.communication.templates.destroy', $template->uuid));

    expect(CommunicationTemplate::withTrashed()->find($template->id)->deleted_at)->not->toBeNull();
});

// ─── Email Campaign ───────────────────────────────────────────────────────

it('creates email campaign in DRAFT status', function (): void {
    $template = CommunicationTemplate::factory()->create([
        'institution_id' => $this->institution->id,
        'channel'        => CommunicationChannel::EMAIL,
    ]);

    $this->actingAs($this->counsellor)
        ->post(route('crm.communication.email.campaigns.store'), [
            'name'        => 'Spring 2025 Campaign',
            'subject'     => 'Admissions Now Open',
            'template_id' => $template->id,
        ]);

    expect(EmailCampaign::where('name', 'Spring 2025 Campaign')->first()?->status)
        ->toBe(CampaignStatus::DRAFT);
});

it('dispatches SendBulkEmailJob on campaign launch', function (): void {
    Queue::fake();

    $campaign = EmailCampaign::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'name'           => 'Launch Test',
        'subject'        => 'Launch subject',
        'status'         => CampaignStatus::DRAFT,
    ]);

    $this->actingAs($this->counsellor)
        ->post(route('crm.communication.email.campaigns.launch', $campaign->uuid));

    Queue::assertPushedOn('crm-comms-email', SendBulkEmailJob::class);
});

// ─── Email Delivery Events ────────────────────────────────────────────────

it('fires EmailSentEvent when email is sent to lead', function (): void {
    Event::fake([EmailSentEvent::class]);

    app(EmailService::class)->sendToLead(
        $this->lead,
        subject: 'Test Email',
        body: 'Hello {{name}}',
        sender: $this->counsellor
    );

    Event::assertDispatched(EmailSentEvent::class, fn ($e) => $e->lead->id === $this->lead->id);
});

it('creates communication log on email send', function (): void {
    app(EmailService::class)->sendToLead(
        $this->lead,
        subject: 'Log Test',
        body: 'Hello',
        sender: $this->counsellor
    );

    expect(CommunicationLog::where('lead_id', $this->lead->id)->exists())->toBeTrue();
});

// ─── Bounce Handling ─────────────────────────────────────────────────────

it('increments bounce count on EmailBouncedEvent', function (): void {
    Event::fake([EmailBouncedEvent::class]);

    $listener = new \App\Listeners\CRM\Communication\HandleEmailBounce();
    $log = CommunicationLog::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'lead_id'        => $this->lead->id,
        'channel'        => CommunicationChannel::EMAIL,
        'direction'      => \App\Enums\CRM\MessageDirection::OUTBOUND,
        'status'         => MessageStatus::DELIVERED,
        'body'           => 'test',
    ]);

    $listener->handle(new EmailBouncedEvent($log));

    expect($this->lead->fresh()->email_bounce_count)->toBe(1);
});

// ─── Unsubscribe (DPDP) ───────────────────────────────────────────────────

it('dispatches EnforceUnsubscribeJob on unsubscribe', function (): void {
    Queue::fake();

    app(EmailService::class)->unsubscribeLead($this->lead);

    Queue::assertPushed(EnforceUnsubscribeJob::class);
});

it('marks lead email_unsubscribed_at within 24h', function (): void {
    $job = new EnforceUnsubscribeJob($this->lead->id, 'email');
    $job->handle(app(EmailService::class));

    expect($this->lead->fresh()->email_unsubscribed_at)->not->toBeNull();
});

it('ensures unsubscribe job is idempotent', function (): void {
    $job = new EnforceUnsubscribeJob($this->lead->id, 'email');
    $job->handle(app(EmailService::class));
    $firstTimestamp = $this->lead->fresh()->email_unsubscribed_at;

    $job->handle(app(EmailService::class));

    expect($this->lead->fresh()->email_unsubscribed_at->toISOString())
        ->toBe($firstTimestamp->toISOString());
});

// ─── Webhook Controller ───────────────────────────────────────────────────

it('rejects email webhook with invalid signature', function (): void {
    $this->post(route('api.crm.webhooks.email', ['provider' => 'mailgun']), [
        'event' => 'delivered',
    ], ['X-Webhook-Signature' => 'bad-sig'])
        ->assertForbidden();
});

it('dispatches ProcessEmailWebhookJob on valid webhook', function (): void {
    Queue::fake();
    config(['services.email.mailgun.webhook_secret' => 'test-secret']);
    $timestamp = (string) now()->timestamp;
    $token     = \Illuminate\Support\Str::random(50);
    $signature = hash_hmac('sha256', $timestamp . $token, 'test-secret');

    $this->post(route('api.crm.webhooks.email', ['provider' => 'mailgun']), [
        'signature' => ['timestamp' => $timestamp, 'token' => $token, 'signature' => $signature],
        'event-data' => ['event' => 'delivered'],
    ])
        ->assertOk();

    Queue::assertPushedOn('crm-comms-email', ProcessEmailWebhookJob::class);
});

// ─── Sender Domain ────────────────────────────────────────────────────────

it('can add a sender domain', function (): void {
    $this->actingAs($this->counsellor)
        ->post(route('crm.settings.sender-domains.store'), [
            'domain' => 'admissions.test.edu',
        ]);

    expect(SenderDomain::where('domain', 'admissions.test.edu')->exists())->toBeTrue();
});

it('shows DNS records page for sender domain', function (): void {
    $domain = SenderDomain::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'domain'         => 'crm.test.edu',
        'spf_verified'   => false,
        'dkim_verified'  => false,
        'dmarc_verified' => false,
    ]);

    $this->actingAs($this->counsellor)
        ->get(route('crm.settings.sender-domains.show', $domain->uuid))
        ->assertOk()
        ->assertSee('crm.test.edu');
});

it('enforces institution scoping on sender domains', function (): void {
    $otherInstitution = Institution::create([
        'name' => 'Other Uni', 'code' => 'OTH2', 'is_active' => true,
    ]);
    SenderDomain::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $otherInstitution->id,
        'domain'         => 'other.edu',
        'spf_verified'   => false,
        'dkim_verified'  => false,
        'dmarc_verified' => false,
    ]);

    $this->actingAs($this->counsellor)
        ->get(route('crm.settings.sender-domains.index'))
        ->assertDontSee('other.edu');
});

it('returns 403 when accessing non-own institution sender domain', function (): void {
    $otherInstitution = Institution::create([
        'name' => 'Forbidden Uni', 'code' => 'FBU3', 'is_active' => true,
    ]);
    $domain = SenderDomain::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $otherInstitution->id,
        'domain'         => 'forbidden.edu',
        'spf_verified'   => false,
        'dkim_verified'  => false,
        'dmarc_verified' => false,
    ]);

    $this->actingAs($this->counsellor)
        ->get(route('crm.settings.sender-domains.show', $domain->uuid))
        ->assertForbidden();
});
