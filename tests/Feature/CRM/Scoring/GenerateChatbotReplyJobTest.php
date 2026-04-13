<?php

declare(strict_types=1);

use App\Events\CRM\ChatbotEscalationEvent;
use App\Jobs\CRM\GenerateChatbotReplyJob;
use App\Models\CRM\ChatLead;
use App\Models\CRM\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('appends AI chatbot reply and escalates human handoff when required', function (): void {
    Event::fake([ChatbotEscalationEvent::class]);

    $institution = Institution::create([
        'name' => 'AI Chat Job Institute',
        'code' => 'AICJOB01',
        'is_active' => true,
    ]);

    $chatLead = ChatLead::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'session_id' => 'sess-ai-7001',
        'visitor_name' => 'Escalation Prospect',
        'transcript' => [
            ['role' => 'user', 'content' => 'I am very frustrated. Please connect me to a human agent now.'],
        ],
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'chat-widget-v1',
        'handoff_status' => 'captured',
        'inbound_messages' => 1,
        'outbound_messages' => 0,
    ]);

    GenerateChatbotReplyJob::dispatchSync($chatLead->uuid);

    $updated = ChatLead::withoutGlobalScopes()->whereKey($chatLead->id)->firstOrFail();

    expect($updated->handoff_status)->toBe('pending_agent');
    expect($updated->outbound_messages)->toBe(1);
    expect(is_array($updated->transcript))->toBeTrue();
    expect((string) ($updated->transcript[1]['role'] ?? ''))->toBe('assistant');
    expect((string) ($updated->transcript[1]['content'] ?? ''))->toContain('connecting you with a counsellor');

    Event::assertDispatched(ChatbotEscalationEvent::class, function (ChatbotEscalationEvent $event) use ($chatLead): bool {
        return $event->chatLead->uuid === $chatLead->uuid
            && $event->reason === 'lead_requested_human_agent';
    });
});
