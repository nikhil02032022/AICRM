<?php

declare(strict_types=1);

// BRD: CRM-CC-011 to CRM-CC-015, CRM-LC-007 — WhatsApp: conversations, inbound, broadcast, auto-lead

use App\Enums\CRM\ConversationStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\WaMessageType;
use App\Events\CRM\Communication\WhatsAppLeadCreatedEvent;
use App\Events\CRM\Communication\WhatsAppMessageReceivedEvent;
use App\Events\CRM\Communication\WhatsAppMessageSentEvent;
use App\Jobs\CRM\Communication\ProcessInboundWhatsAppJob;
use App\Jobs\CRM\Communication\SendBulkWhatsAppJob;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\WhatsAppConversation;
use App\Models\CRM\WhatsAppMessage;
use App\Models\User;
use App\Services\CRM\Communication\WhatsAppService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->institution = Institution::create([
        'name' => 'WA Test University', 'code' => 'WAT3', 'is_active' => true,
    ]);

    $this->counsellor = User::create([
        'name'           => 'WA Counsellor',
        'email'          => 'wa-counsellor@wat.com',
        'password'       => bcrypt('password'),
        'institution_id' => $this->institution->id,
    ]);
    $this->counsellor->givePermissionTo(['crm.communication.send', 'crm.leads.view']);

    $this->lead = Lead::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'name'           => 'WA Student',
        'email'          => 'wa@example.com',
        'mobile'         => '9876543212',
        'consent_given'  => true,
    ]);
});

// ─── WhatsApp Meta Webhook Challenge ─────────────────────────────────────

it('responds to Meta webhook verify challenge', function (): void {
    config(['services.whatsapp.meta.verify_token' => 'my-verify-token']);

    $this->get(route('api.crm.webhooks.whatsapp.verify') . '?' . http_build_query([
        'hub_mode'         => 'subscribe',
        'hub_verify_token' => 'my-verify-token',
        'hub_challenge'    => 'challenge-xyz',
    ]))
        ->assertOk()
        ->assertSee('challenge-xyz');
});

it('rejects Meta webhook verify with wrong token', function (): void {
    config(['services.whatsapp.meta.verify_token' => 'correct-token']);

    $this->get(route('api.crm.webhooks.whatsapp.verify') . '?' . http_build_query([
        'hub_mode'         => 'subscribe',
        'hub_verify_token' => 'wrong-token',
        'hub_challenge'    => 'challenge-xyz',
    ]))
        ->assertForbidden();
});

// ─── Inbound Webhook → Job Dispatch ──────────────────────────────────────

it('dispatches ProcessInboundWhatsAppJob on valid Meta webhook', function (): void {
    Queue::fake();
    config(['services.whatsapp.meta.app_secret' => 'test-secret']);

    $payload = json_encode(['object' => 'whatsapp_business_account', 'entry' => []]);
    $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-secret');

    $this->post(
        route('api.crm.webhooks.whatsapp.receive'),
        json_decode($payload, true),
        ['X-Hub-Signature-256' => $signature]
    )->assertOk();

    Queue::assertPushedOn('crm-comms-whatsapp', ProcessInboundWhatsAppJob::class);
});

it('rejects WhatsApp webhook with invalid signature', function (): void {
    config(['services.whatsapp.meta.app_secret' => 'test-secret']);

    $this->post(
        route('api.crm.webhooks.whatsapp.receive'),
        ['object' => 'whatsapp_business_account'],
        ['X-Hub-Signature-256' => 'sha256=bad']
    )->assertForbidden();
});

// ─── LC-007 — Auto-lead creation from WhatsApp ────────────────────────────

it('auto-creates lead from WhatsApp number not in system (LC-007)', function (): void {
    Event::fake([WhatsAppLeadCreatedEvent::class]);

    $payload = [
        'institution_id' => $this->institution->id,
        'wa_phone'       => '+919999999999',
        'wa_name'        => 'New Student',
        'message_id'     => 'wamid.test123',
        'message_body'   => 'Hello, I want to know about admissions',
        'message_type'   => WaMessageType::TEXT->value,
    ];

    app(WhatsAppService::class)->handleInboundMessage($payload);

    $lead = Lead::where('mobile', '9999999999')->first();
    expect($lead)->not->toBeNull();
    expect($lead?->source)->toBe(LeadSource::WHATSAPP);
    Event::assertDispatched(WhatsAppLeadCreatedEvent::class);
});

it('sets consent_given=false on auto-created WhatsApp lead (DPDP)', function (): void {
    $payload = [
        'institution_id' => $this->institution->id,
        'wa_phone'       => '+919888888888',
        'wa_name'        => 'Anonymous Student',
        'message_id'     => 'wamid.dpdp001',
        'message_body'   => 'Info please',
        'message_type'   => WaMessageType::TEXT->value,
    ];

    app(WhatsAppService::class)->handleInboundMessage($payload);

    $lead = Lead::where('mobile', '9888888888')->first();
    expect($lead?->consent_given)->toBeFalse();
});

// ─── Conversation Management ──────────────────────────────────────────────

it('creates a WhatsApp conversation on first inbound message', function (): void {
    $payload = [
        'institution_id' => $this->institution->id,
        'wa_phone'       => '+919777777777',
        'wa_name'        => 'Conv Student',
        'message_id'     => 'wamid.conv001',
        'message_body'   => 'Hi!',
        'message_type'   => WaMessageType::TEXT->value,
    ];

    app(WhatsAppService::class)->handleInboundMessage($payload);

    expect(WhatsAppConversation::where('wa_phone_number', '9777777777')->exists())->toBeTrue();
});

it('appends message to existing conversation on repeat inbound', function (): void {
    $conv = WhatsAppConversation::create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'institution_id'  => $this->institution->id,
        'lead_id'         => $this->lead->id,
        'wa_phone_number' => '9876543212',
        'status'          => ConversationStatus::OPEN,
    ]);

    app(WhatsAppService::class)->handleInboundMessage([
        'institution_id'  => $this->institution->id,
        'wa_phone'        => '+919876543212',
        'wa_name'         => 'WA Student',
        'message_id'      => 'wamid.repeat001',
        'message_body'    => 'Second message',
        'message_type'    => WaMessageType::TEXT->value,
    ]);

    expect(WhatsAppMessage::where('conversation_id', $conv->id)->count())->toBe(1);
});

// ─── Send Message + Events ───────────────────────────────────────────────

it('fires WhatsAppMessageSentEvent on outbound message', function (): void {
    Event::fake([WhatsAppMessageSentEvent::class]);

    $conv = WhatsAppConversation::create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'institution_id'  => $this->institution->id,
        'lead_id'         => $this->lead->id,
        'wa_phone_number' => '9876543212',
        'status'          => ConversationStatus::OPEN,
    ]);

    app(WhatsAppService::class)->sendMessage($conv, 'Hello student!', $this->counsellor);

    Event::assertDispatched(WhatsAppMessageSentEvent::class);
});

it('fires WhatsAppMessageReceivedEvent on inbound message', function (): void {
    Event::fake([WhatsAppMessageReceivedEvent::class]);

    app(WhatsAppService::class)->handleInboundMessage([
        'institution_id' => $this->institution->id,
        'wa_phone'       => '+919876543212',
        'wa_name'        => 'WA Student',
        'message_id'     => 'wamid.rcv001',
        'message_body'   => 'Inbound test',
        'message_type'   => WaMessageType::TEXT->value,
    ]);

    Event::assertDispatched(WhatsAppMessageReceivedEvent::class);
});

// ─── Broadcast ────────────────────────────────────────────────────────────

it('dispatches SendBulkWhatsAppJob for broadcast', function (): void {
    Queue::fake();

    app(WhatsAppService::class)->dispatchBroadcast(
        leadIds: [$this->lead->id],
        templateName: 'admission_open',
        languageCode: 'en',
        institutionId: $this->institution->id,
    );

    Queue::assertPushedOn('crm-comms-whatsapp', SendBulkWhatsAppJob::class);
});

// ─── WA Inbox Web Routes ─────────────────────────────────────────────────

it('shows WhatsApp inbox to authenticated counsellor', function (): void {
    $this->actingAs($this->counsellor)
        ->get(route('crm.communication.whatsapp.index'))
        ->assertOk();
});

it('redirects to login for unauthenticated WhatsApp inbox', function (): void {
    $this->get(route('crm.communication.whatsapp.index'))
        ->assertRedirect(route('login'));
});

// ─── Encryption (DPDP) ────────────────────────────────────────────────────

it('encrypts wa_phone_number at rest in WhatsAppConversation', function (): void {
    $conv = WhatsAppConversation::create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'institution_id'  => $this->institution->id,
        'lead_id'         => $this->lead->id,
        'wa_phone_number' => '9876543212',
        'status'          => ConversationStatus::OPEN,
    ]);

    $raw = \DB::table('whatsapp_conversations')->where('id', $conv->id)->value('wa_phone_number');
    expect($raw)->not->toBe('9876543212');
    expect($conv->wa_phone_number)->toBe('9876543212');
});

it('encrypts WhatsApp message body at rest', function (): void {
    $conv = WhatsAppConversation::create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'institution_id'  => $this->institution->id,
        'lead_id'         => $this->lead->id,
        'wa_phone_number' => '9876543212',
        'status'          => ConversationStatus::OPEN,
    ]);

    $msg = WhatsAppMessage::create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'conversation_id' => $conv->id,
        'direction'       => MessageDirection::OUTBOUND,
        'wa_message_type' => WaMessageType::TEXT,
        'body'            => 'Secret message content',
        'status'          => \App\Enums\CRM\MessageStatus::SENT,
        'bsp_message_id'  => 'wamid.enc001',
    ]);

    $raw = \DB::table('whatsapp_messages')->where('id', $msg->id)->value('body');
    expect($raw)->not->toBe('Secret message content');
    expect($msg->body)->toBe('Secret message content');
});

// ─── Multi-tenancy ────────────────────────────────────────────────────────

it('does not show other institution WhatsApp conversations', function (): void {
    $other = Institution::create([
        'name' => 'Other WA Uni', 'code' => 'OWU4', 'is_active' => true,
    ]);
    $otherLead = Lead::create([
        'uuid' => \Illuminate\Support\Str::uuid(), 'institution_id' => $other->id,
        'name' => 'Other WA Student', 'mobile' => '9111111111',
        'email' => 'other@wa.com', 'consent_given' => true,
    ]);
    WhatsAppConversation::create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'institution_id'  => $other->id,
        'lead_id'         => $otherLead->id,
        'wa_phone_number' => '9111111111',
        'status'          => ConversationStatus::OPEN,
    ]);

    $count = WhatsAppConversation::count();
    expect($count)->toBe(0); // InstitutionScope filters out other institution
});
