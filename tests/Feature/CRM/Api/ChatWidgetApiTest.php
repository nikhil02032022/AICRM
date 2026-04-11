<?php

declare(strict_types=1);

use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('captures a chat lead via API and creates a linked CRM lead', function (): void {
    $institution = Institution::create([
        'name' => 'Chat API University',
        'code' => 'CHATAPI01',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Marketing API Admin',
        'email' => 'chat-api-admin@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo('crm.chat-widget.manage');

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/chat-widget/leads', [
            'session_id' => 'sess-api-1001',
            'first_name' => 'Aarav',
            'last_name' => 'Kapoor',
            'mobile' => '9876543210',
            'email' => 'aarav@example.com',
            'source_url' => 'https://campaign.example.com/mba',
            'transcript' => [
                ['role' => 'assistant', 'content' => 'Hello, how can we help?'],
                ['role' => 'user', 'content' => 'I want MBA admission details for 2027.'],
            ],
            'source_utm_params' => [
                'utm_source' => 'google_ads',
                'utm_campaign' => 'mba-2027',
            ],
            'consent_given' => true,
            'consent_form_version' => 'chat-widget-v1',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Chat lead captured successfully.')
        ->assertJsonPath('data.session_id', 'sess-api-1001');

    $this->assertDatabaseHas('chat_leads', [
        'institution_id' => $institution->id,
        'session_id' => 'sess-api-1001',
        'consent_form_version' => 'chat-widget-v1',
    ]);

    $this->assertDatabaseHas('leads', [
        'institution_id' => $institution->id,
        'source' => 'live_chat',
        'consent_given' => true,
    ]);
});

it('adds a staff reply to a chat lead transcript via API', function (): void {
    $institution = Institution::create([
        'name' => 'Chat Reply University',
        'code' => 'CHATAPI02',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Counsellor Agent',
        'email' => 'chat-agent@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo('crm.chat-widget.manage');

    $createResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/chat-widget/leads', [
            'session_id' => 'sess-api-2001',
            'first_name' => 'Meera',
            'last_name' => 'Iyer',
            'mobile' => '9876543220',
            'email' => 'meera@example.com',
            'transcript' => [
                ['role' => 'user', 'content' => 'Can I apply for BBA online?'],
            ],
            'consent_given' => true,
            'consent_form_version' => 'chat-widget-v1',
        ]);

    $chatLeadUuid = (string) $createResponse->json('data.uuid');

    $replyResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/chat-widget/leads/'.$chatLeadUuid.'/reply', [
            'message' => 'Yes. I have sent the application link to your registered email.',
        ]);

    $replyResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.handoff_status', 'live_agent')
        ->assertJsonPath('data.outbound_messages', 1);

    $this->assertDatabaseHas('chat_leads', [
        'uuid' => $chatLeadUuid,
        'assigned_to' => $user->id,
        'handoff_status' => 'live_agent',
    ]);
});

it('updates handoff status for a chat lead via API', function (): void {
    $institution = Institution::create([
        'name' => 'Chat Handoff University',
        'code' => 'CHATAPI03',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Chat Supervisor',
        'email' => 'chat-supervisor@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo('crm.chat-widget.manage');

    $createResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/chat-widget/leads', [
            'session_id' => 'sess-api-3001',
            'first_name' => 'Rohan',
            'last_name' => 'Malhotra',
            'mobile' => '9876543230',
            'email' => 'rohan@example.com',
            'transcript' => [
                ['role' => 'assistant', 'content' => 'Welcome to admissions support.'],
                ['role' => 'user', 'content' => 'Please connect me with a counsellor.'],
            ],
            'consent_given' => true,
            'consent_form_version' => 'chat-widget-v1',
        ]);

    $chatLeadUuid = (string) $createResponse->json('data.uuid');

    $handoffResponse = $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/crm/chat-widget/leads/'.$chatLeadUuid.'/handoff', [
            'handoff_status' => 'resolved',
            'assigned_to' => $user->id,
        ]);

    $handoffResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.handoff_status', 'resolved')
        ->assertJsonPath('data.assigned_to.id', $user->id);

    $this->assertDatabaseHas('chat_leads', [
        'uuid' => $chatLeadUuid,
        'handoff_status' => 'resolved',
        'assigned_to' => $user->id,
    ]);
});
