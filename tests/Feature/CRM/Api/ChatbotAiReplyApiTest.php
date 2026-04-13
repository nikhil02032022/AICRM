<?php

declare(strict_types=1);

use App\Jobs\CRM\GenerateChatbotReplyJob;
use App\Models\CRM\ChatLead;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('queues AI chatbot reply generation via API', function (): void {
    Queue::fake();

    $institution = Institution::create([
        'name' => 'AI Chat API Institute',
        'code' => 'AICAPI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'AI Chat Operator',
        'email' => 'ai-chat-operator@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo('crm.chat-widget.manage');

    $chatLead = ChatLead::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'session_id' => 'sess-ai-6001',
        'visitor_name' => 'Prospect Student',
        'transcript' => [
            ['role' => 'user', 'content' => 'Can you share fee details?'],
        ],
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'chat-widget-v1',
        'handoff_status' => 'captured',
        'inbound_messages' => 1,
        'outbound_messages' => 0,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/chat-widget/leads/'.$chatLead->uuid.'/ai-reply');

    $response->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'AI chatbot reply generation queued.')
        ->assertJsonPath('data.chat_lead_uuid', $chatLead->uuid);

    Queue::assertPushed(GenerateChatbotReplyJob::class, function (GenerateChatbotReplyJob $job) use ($chatLead): bool {
        return $job->chatLeadUuid === $chatLead->uuid;
    });
});
