<?php

declare(strict_types=1);

use App\Models\CRM\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts public chat widget submissions and creates lead plus chat ledger', function (): void {
    $institution = Institution::create([
        'name' => 'Public Chat University',
        'code' => 'PUBCHAT01',
        'is_active' => true,
    ]);

    $response = $this->postJson('/chat/widget/'.$institution->uuid.'/submit', [
        'session_id' => 'sess-public-2001',
        'first_name' => 'Diya',
        'last_name' => 'Sharma',
        'mobile' => '9876543211',
        'email' => 'diya@example.com',
        'source_url' => 'https://www.publicchat.example/admissions',
        'transcript' => [
            ['role' => 'assistant', 'content' => 'Welcome to admissions support.'],
            ['role' => 'user', 'content' => 'Can I get BCA fee details?'],
        ],
        'consent_given' => true,
        'consent_form_version' => 'chat-widget-v1',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Thank you. Your chat enquiry has been captured.');

    $this->assertDatabaseHas('chat_leads', [
        'institution_id' => $institution->id,
        'session_id' => 'sess-public-2001',
        'consent_form_version' => 'chat-widget-v1',
    ]);

    $this->assertDatabaseHas('leads', [
        'institution_id' => $institution->id,
        'source' => 'live_chat',
        'consent_given' => true,
    ]);
});

it('rejects public chat widget submission without consent', function (): void {
    $institution = Institution::create([
        'name' => 'Public Chat University 2',
        'code' => 'PUBCHAT02',
        'is_active' => true,
    ]);

    $this->postJson('/chat/widget/'.$institution->uuid.'/submit', [
        'session_id' => 'sess-public-2002',
        'first_name' => 'Kabir',
        'last_name' => 'Patel',
        'mobile' => '9876543212',
        'consent_given' => false,
        'consent_form_version' => 'chat-widget-v1',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['consent_given']);
});
