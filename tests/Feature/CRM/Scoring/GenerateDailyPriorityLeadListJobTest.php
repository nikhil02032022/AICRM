<?php

declare(strict_types=1);

use App\Jobs\CRM\GenerateDailyPriorityLeadListJob;
use App\Models\CRM\CounsellorPriorityLead;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('generates ranked daily priority leads for assigned counsellor', function (): void {
    $institution = Institution::create([
        'name' => 'Priority Job Institute',
        'code' => 'PJI',
        'is_active' => true,
    ]);

    $counsellor = User::create([
        'name' => 'Counsellor One',
        'email' => 'counsellor1@priority.test',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'assigned_counsellor_id' => $counsellor->id,
        'first_name' => 'High',
        'last_name' => 'Priority',
        'mobile' => '9876501001',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 78,
        'updated_at' => now()->subDays(10),
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'assigned_counsellor_id' => $counsellor->id,
        'first_name' => 'Low',
        'last_name' => 'Priority',
        'mobile' => '9876501002',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'cold',
        'lead_score' => 35,
        'updated_at' => now()->subDays(2),
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    GenerateDailyPriorityLeadListJob::dispatchSync($institution->id, now()->toDateString());

    $rows = CounsellorPriorityLead::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->where('counsellor_id', $counsellor->id)
        ->whereDate('generated_for_date', now()->toDateString())
        ->orderBy('priority_rank')
        ->get();

    expect($rows)->toHaveCount(2);
    expect($rows->first()->priority_rank)->toBe(1);
    expect($rows->first()->priority_score)->toBeGreaterThan($rows->last()->priority_score);
});
