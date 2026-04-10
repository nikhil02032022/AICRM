<?php

declare(strict_types=1);

// BRD: CRM-CC-021 to CRM-CC-025 — Unified inbox, notifications, polling

use App\Enums\CRM\ConversationStatus;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\WaMessageType;
use App\Enums\CRM\MessageStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\WhatsAppConversation;
use App\Models\CRM\WhatsAppMessage;
use App\Models\User;
use App\Notifications\CRM\InboundMessageNotification;
use App\Notifications\CRM\MissedCallNotification;
use App\Services\CRM\Communication\UnifiedInboxService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->institution = Institution::create([
        'name' => 'Inbox Test University', 'code' => 'ITU6', 'is_active' => true,
    ]);

    $this->counsellor = User::create([
        'name'           => 'Inbox Counsellor',
        'email'          => 'inbox@itu.com',
        'password'       => bcrypt('password'),
        'institution_id' => $this->institution->id,
    ]);
    $this->counsellor->givePermissionTo(['crm.communication.send', 'crm.leads.view']);

    $this->lead = Lead::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'name'           => 'Inbox Student',
        'email'          => 'inbox@example.com',
        'mobile'         => '9876543214',
        'consent_given'  => true,
    ]);

    $this->conversation = WhatsAppConversation::create([
        'uuid'                    => \Illuminate\Support\Str::uuid(),
        'institution_id'          => $this->institution->id,
        'lead_id'                 => $this->lead->id,
        'wa_phone_number'         => '9876543214',
        'status'                  => ConversationStatus::OPEN,
        'assigned_counsellor_id'  => $this->counsellor->id,
    ]);
});

// ─── Unified Inbox: index ───────────────────────────────────────────────

it('shows unified inbox to authenticated counsellor', function (): void {
    $this->actingAs($this->counsellor)
        ->get(route('crm.inbox.index'))
        ->assertOk();
});

it('returns 302 for unauthenticated inbox access', function (): void {
    $this->get(route('crm.inbox.index'))
        ->assertRedirect(route('login'));
});

// ─── UnifiedInboxService ────────────────────────────────────────────────

it('returns inbox items for counsellor', function (): void {
    WhatsAppMessage::create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'conversation_id' => $this->conversation->id,
        'direction'       => MessageDirection::INBOUND,
        'wa_message_type' => WaMessageType::TEXT,
        'body'            => 'Hello counsellor',
        'status'          => MessageStatus::RECEIVED,
        'bsp_message_id'  => 'wamid.inbox001',
    ]);

    $inbox = app(UnifiedInboxService::class)->getInboxForCounsellor($this->counsellor);

    expect($inbox)->not->toBeEmpty();
});

it('returns unread counts per channel', function (): void {
    WhatsAppMessage::create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'conversation_id' => $this->conversation->id,
        'direction'       => MessageDirection::INBOUND,
        'wa_message_type' => WaMessageType::TEXT,
        'body'            => 'Unread message',
        'status'          => MessageStatus::RECEIVED,
        'bsp_message_id'  => 'wamid.unread001',
        'read_at'         => null,
    ]);

    $counts = app(UnifiedInboxService::class)->getUnreadCounts($this->counsellor);

    expect($counts['whatsapp'] ?? 0)->toBeGreaterThan(0);
});

// ─── Mark as Read ────────────────────────────────────────────────────────

it('marks conversation messages as read via web', function (): void {
    WhatsAppMessage::create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'conversation_id' => $this->conversation->id,
        'direction'       => MessageDirection::INBOUND,
        'wa_message_type' => WaMessageType::TEXT,
        'body'            => 'Unread',
        'status'          => MessageStatus::RECEIVED,
        'bsp_message_id'  => 'wamid.read001',
        'read_at'         => null,
    ]);

    $this->actingAs($this->counsellor)
        ->post(route('crm.inbox.mark-read', $this->conversation->uuid));

    $allRead = WhatsAppMessage::where('conversation_id', $this->conversation->id)
        ->whereNull('read_at')
        ->doesntExist();

    expect($allRead)->toBeTrue();
});

// ─── Assign Conversation ─────────────────────────────────────────────────

it('can assign conversation to counsellor', function (): void {
    $other = User::create([
        'name'           => 'Other Counsellor',
        'email'          => 'other2@itu.com',
        'password'       => bcrypt('password'),
        'institution_id' => $this->institution->id,
    ]);

    $this->actingAs($this->counsellor)
        ->post(route('crm.inbox.assign', $this->conversation->uuid), [
            'counsellor_id' => $other->id,
        ]);

    expect($this->conversation->fresh()->assigned_counsellor_id)->toBe($other->id);
});

// ─── Notifications ────────────────────────────────────────────────────────

it('sends InboundMessageNotification to assigned counsellor', function (): void {
    Notification::fake();

    WhatsAppMessage::create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'conversation_id' => $this->conversation->id,
        'direction'       => MessageDirection::INBOUND,
        'wa_message_type' => WaMessageType::TEXT,
        'body'            => 'Notify me',
        'status'          => MessageStatus::RECEIVED,
        'bsp_message_id'  => 'wamid.notif001',
    ]);

    $listener = new \App\Listeners\CRM\Communication\NotifyAssignedCounsellorOnInbound();
    $message  = WhatsAppMessage::where('bsp_message_id', 'wamid.notif001')->first();
    $listener->handle(new \App\Events\CRM\Communication\WhatsAppMessageReceivedEvent($message));

    Notification::assertSentTo($this->counsellor, InboundMessageNotification::class);
});

it('sends MissedCallNotification to counsellor', function (): void {
    Notification::fake();

    $callLog = \App\Models\CRM\CallLog::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'lead_id'        => $this->lead->id,
        'direction'      => \App\Enums\CRM\CallDirection::INBOUND,
        'status'         => \App\Enums\CRM\CallStatus::NO_ANSWER,
    ]);

    $listener = new \App\Listeners\CRM\Communication\NotifyCounsellorOnMissedCall();
    $listener->handle(new \App\Events\CRM\Communication\MissedCallReceivedEvent($callLog, $this->counsellor));

    Notification::assertSentTo($this->counsellor, MissedCallNotification::class);
});

// ─── Unread Counts endpoint ───────────────────────────────────────────────

it('returns JSON unread counts via web polling endpoint', function (): void {
    $this->actingAs($this->counsellor)
        ->get(route('crm.inbox.unread-counts'))
        ->assertOk()
        ->assertJsonStructure(['counts']);
});

// ─── Multi-tenancy ────────────────────────────────────────────────────────

it('does not expose other institution conversations in inbox', function (): void {
    $otherInst = Institution::create([
        'name' => 'Other Inbox Uni', 'code' => 'OIU7', 'is_active' => true,
    ]);
    $otherUser = User::create([
        'name' => 'Other', 'email' => 'inbox-other@itu.com',
        'password' => bcrypt('password'), 'institution_id' => $otherInst->id,
    ]);
    $otherLead = Lead::create([
        'uuid' => \Illuminate\Support\Str::uuid(), 'institution_id' => $otherInst->id,
        'name' => 'Other Lead', 'mobile' => '9222222222', 'consent_given' => true,
    ]);
    WhatsAppConversation::withoutGlobalScopes()->create([
        'uuid'            => \Illuminate\Support\Str::uuid(),
        'institution_id'  => $otherInst->id,
        'lead_id'         => $otherLead->id,
        'wa_phone_number' => '9222222222',
        'status'          => ConversationStatus::OPEN,
    ]);

    $inbox = app(UnifiedInboxService::class)->getInboxForCounsellor($this->counsellor);

    $leadsInInbox = collect($inbox)->pluck('name');
    expect($leadsInInbox)->not->toContain('Other Lead');
});
