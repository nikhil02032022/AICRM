<?php

declare(strict_types=1);

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Portal\PortalMessage;
use App\Services\CRM\Portal\PortalAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

function chatSetup(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $lead        = Lead::factory()->create(['institution_id' => $institution->id]);

    return [$institution, $lead];
}

function chatSession(Lead $lead, Institution $institution): string
{
    return app(PortalAuthService::class)->issueSession($lead, $institution);
}

function chatParam(Institution $institution): string
{
    return '?institution=' . $institution->uuid;
}

// ──────────────────────────────────────────────────────────────
// Auth guard
// ──────────────────────────────────────────────────────────────

it('redirects unauthenticated visitor from chat index to login', function (): void {
    [$institution] = chatSetup();

    $this->get('/portal/chat' . chatParam($institution))
        ->assertRedirect(route('portal.auth.login'));
});

it('redirects unauthenticated POST to login', function (): void {
    [$institution] = chatSetup();

    $this->post('/portal/chat' . chatParam($institution), ['body' => 'hello'])
        ->assertRedirect(route('portal.auth.login'));
});

// ──────────────────────────────────────────────────────────────
// Index — empty thread
// ──────────────────────────────────────────────────────────────

it('renders chat index with empty state when no messages exist', function (): void {
    [$institution, $lead] = chatSetup();
    $token = chatSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/chat' . chatParam($institution))
        ->assertOk()
        ->assertSee('No messages yet');
});

// ──────────────────────────────────────────────────────────────
// Index — with messages
// ──────────────────────────────────────────────────────────────

it('renders inbound and outbound messages in the thread', function (): void {
    [$institution, $lead] = chatSetup();
    $token = chatSession($lead, $institution);

    PortalMessage::create([
        'lead_uuid'      => $lead->uuid,
        'institution_id' => $institution->id,
        'direction'      => MessageDirection::INBOUND,
        'body'           => 'Hello counsellor!',
    ]);

    PortalMessage::create([
        'lead_uuid'      => $lead->uuid,
        'institution_id' => $institution->id,
        'direction'      => MessageDirection::OUTBOUND,
        'body'           => 'Hello applicant!',
        'sent_by_user_id' => 1,
    ]);

    $this->withCookie('portal_session', $token)
        ->get('/portal/chat' . chatParam($institution))
        ->assertOk()
        ->assertSee('Hello counsellor!')
        ->assertSee('Hello applicant!');
});

it('marks unread outbound messages as read when applicant opens thread', function (): void {
    [$institution, $lead] = chatSetup();
    $token = chatSession($lead, $institution);

    PortalMessage::create([
        'lead_uuid'      => $lead->uuid,
        'institution_id' => $institution->id,
        'direction'      => MessageDirection::OUTBOUND,
        'body'           => 'Please upload your transcript.',
        'sent_by_user_id' => 1,
    ]);

    expect(PortalMessage::whereNull('applicant_read_at')->count())->toBe(1);

    $this->withCookie('portal_session', $token)
        ->get('/portal/chat' . chatParam($institution));

    expect(PortalMessage::whereNull('applicant_read_at')->count())->toBe(0);
});

it('does not mark inbound (applicant) messages as read on open', function (): void {
    [$institution, $lead] = chatSetup();
    $token = chatSession($lead, $institution);

    PortalMessage::create([
        'lead_uuid'      => $lead->uuid,
        'institution_id' => $institution->id,
        'direction'      => MessageDirection::INBOUND,
        'body'           => 'My message',
    ]);

    $this->withCookie('portal_session', $token)
        ->get('/portal/chat' . chatParam($institution));

    // inbound messages don't have applicant_read_at semantics — field stays null
    expect(PortalMessage::where('direction', MessageDirection::INBOUND->value)
        ->whereNull('applicant_read_at')->count())->toBe(1);
});

// ──────────────────────────────────────────────────────────────
// Store — send message
// ──────────────────────────────────────────────────────────────

it('stores a new inbound portal message and redirects', function (): void {
    [$institution, $lead] = chatSetup();
    $token = chatSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->post('/portal/chat' . chatParam($institution), ['body' => 'I have a question.'])
        ->assertRedirect(route('portal.chat.index'));

    expect(PortalMessage::count())->toBe(1);
    $msg = PortalMessage::first();
    expect($msg->body)->toBe('I have a question.')
        ->and($msg->direction)->toBe(MessageDirection::INBOUND)
        ->and($msg->lead_uuid)->toBe($lead->uuid);
});

it('writes a CommunicationLog entry for CC-021 inbox visibility on send', function (): void {
    [$institution, $lead] = chatSetup();
    $token = chatSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->post('/portal/chat' . chatParam($institution), ['body' => 'Please review my documents.']);

    $log = CommunicationLog::withoutGlobalScopes()->first();
    expect($log)->not->toBeNull()
        ->and($log->channel)->toBe(CommunicationChannel::PORTAL)
        ->and($log->direction)->toBe(MessageDirection::INBOUND)
        ->and($log->lead_id)->toBe($lead->id)
        ->and($log->institution_id)->toBe($institution->id);
});

it('truncates body_preview in CommunicationLog to 150 chars', function (): void {
    [$institution, $lead] = chatSetup();
    $token = chatSession($lead, $institution);

    $longBody = str_repeat('a', 200);

    $this->withCookie('portal_session', $token)
        ->post('/portal/chat' . chatParam($institution), ['body' => $longBody]);

    $log = CommunicationLog::withoutGlobalScopes()->first();
    expect(mb_strlen($log->body_preview))->toBe(150);
});

// ──────────────────────────────────────────────────────────────
// Validation
// ──────────────────────────────────────────────────────────────

it('rejects an empty message body', function (): void {
    [$institution, $lead] = chatSetup();
    $token = chatSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->post('/portal/chat' . chatParam($institution), ['body' => ''])
        ->assertSessionHasErrors('body');

    expect(PortalMessage::count())->toBe(0);
});

it('rejects a message body exceeding 2000 characters', function (): void {
    [$institution, $lead] = chatSetup();
    $token = chatSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->post('/portal/chat' . chatParam($institution), ['body' => str_repeat('x', 2001)])
        ->assertSessionHasErrors('body');

    expect(PortalMessage::count())->toBe(0);
});

// ──────────────────────────────────────────────────────────────
// Cross-applicant isolation
// ──────────────────────────────────────────────────────────────

it('does not show messages belonging to a different lead', function (): void {
    [$institution, $lead] = chatSetup();
    $otherLead = Lead::factory()->create(['institution_id' => $institution->id]);

    PortalMessage::create([
        'lead_uuid'      => $otherLead->uuid,
        'institution_id' => $institution->id,
        'direction'      => MessageDirection::INBOUND,
        'body'           => 'Other lead message',
    ]);

    $token = chatSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/chat' . chatParam($institution))
        ->assertOk()
        ->assertDontSee('Other lead message');
});
