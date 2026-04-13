<?php

declare(strict_types=1);

use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('renders follow-up session create page with prompt banner', function (): void {
    /** @var \Tests\TestCase $this */
    $this->seed(PermissionSeeder::class);

    $institution = Institution::create([
        'name' => 'Followup Institute',
        'code' => 'FUI',
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Followup Counsellor',
        'email' => 'followup@test.local',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.sessions.create']);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Lina',
        'last_name' => 'Shah',
        'mobile' => '9876500066',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    $this->actingAs($user)
        ->withSession([
            'follow_up_prompt' => [
                'call_log_uuid' => 'dummy-call',
                'disposition' => 'CALL_BACK',
                'disposition_label' => 'Call Back Requested',
            ],
        ])
        ->get(route('crm.leads.sessions.create', $lead->uuid))
        ->assertOk()
        ->assertSee('Schedule Follow-up Session')
        ->assertSee('Post-call prompt: Call Back Requested');
});
