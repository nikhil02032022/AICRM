<?php

declare(strict_types=1);

use App\Models\CRM\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts kiosk submission and creates a walk-in lead', function (): void {
    $institution = Institution::create([
        'name' => 'Campus Walk-in University',
        'code' => 'CWU01',
        'is_active' => true,
    ]);

    $response = $this->postJson('/kiosk/'.$institution->uuid.'/submit', [
        'first_name' => 'Aarav',
        'last_name' => 'Iyer',
        'mobile' => '9876543222',
        'email' => 'aarav@example.com',
        'query_message' => 'Need B.Com fee details and scholarship eligibility.',
        'kiosk_label' => 'Reception Desk',
        'consent_given' => true,
        'consent_form_version' => 'kiosk-v1',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Walk-in enquiry captured successfully.');

    $this->assertDatabaseHas('leads', [
        'institution_id' => $institution->id,
        'source' => 'walk_in',
        'consent_given' => true,
        'consent_form_version' => 'kiosk-v1',
    ]);
});

it('rejects kiosk submission without consent', function (): void {
    $institution = Institution::create([
        'name' => 'Campus Walk-in University 2',
        'code' => 'CWU02',
        'is_active' => true,
    ]);

    $this->postJson('/kiosk/'.$institution->uuid.'/submit', [
        'first_name' => 'Meera',
        'last_name' => 'Nair',
        'mobile' => '9876543223',
        'query_message' => 'Need hostel information.',
        'consent_given' => false,
        'consent_form_version' => 'kiosk-v1',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['consent_given']);
});

it('returns 404 for inactive institution kiosk submission', function (): void {
    $institution = Institution::create([
        'name' => 'Inactive Walk-in University',
        'code' => 'IWU01',
        'is_active' => false,
    ]);

    $this->postJson('/kiosk/'.$institution->uuid.'/submit', [
        'first_name' => 'Karan',
        'last_name' => 'Rao',
        'mobile' => '9876543224',
        'query_message' => 'Need PGDM admission guidance.',
        'consent_given' => true,
        'consent_form_version' => 'kiosk-v1',
    ])->assertNotFound();
});
