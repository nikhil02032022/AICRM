<?php

declare(strict_types=1);

// BRD: CRM-CR-003 — Opt-out/unsubscribe honoured within 24 hours and logged

use App\Enums\CRM\Compliance\OptOutChannel;
use App\Jobs\CRM\Compliance\ProcessOptOutJob;
use App\Models\CRM\Compliance\OptOutLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Compliance\OptOutService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->user        = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->service     = app(OptOutService::class);

    $this->lead = Lead::withoutGlobalScopes()->create([
        'institution_id'   => $this->institution->id,
        'first_name'       => 'Kiran',
        'last_name'        => 'Rao',
        'email'            => encrypt('kiran@example.com'),
        'mobile'           => encrypt('9000111222'),
        'source'           => 'walk_in',
        'status'           => 'new_enquiry',
        'consent_given'    => true,
        'consent_timestamp' => now(),
        'opt_out'          => false,
    ]);
});

it('opt-out request creates OptOutLog', function (): void {
    $this->service->request($this->lead, OptOutChannel::Email);

    expect(
        OptOutLog::withoutGlobalScopes()
            ->where('lead_id', $this->lead->id)
            ->exists()
    )->toBeTrue();
});

it('OptOutLog has correct channel and lead_id', function (): void {
    $log = $this->service->request($this->lead, OptOutChannel::Email);

    expect($log->lead_id)->toBe($this->lead->id);
    expect($log->channel->value)->toBe(OptOutChannel::Email->value);
    expect($log->institution_id)->toBe($this->institution->id);
});

it('ProcessOptOutJob marks lead opt_out = true', function (): void {
    $log = OptOutLog::withoutGlobalScopes()->create([
        'lead_id'        => $this->lead->id,
        'institution_id' => $this->institution->id,
        'channel'        => OptOutChannel::Email->value,
        'requested_at'   => now(),
    ]);

    ProcessOptOutJob::dispatchSync();

    expect($this->lead->fresh()->opt_out)->toBeTrue();
});

it('ProcessOptOutJob sets processed_at on the OptOutLog', function (): void {
    $log = OptOutLog::withoutGlobalScopes()->create([
        'lead_id'        => $this->lead->id,
        'institution_id' => $this->institution->id,
        'channel'        => OptOutChannel::SMS->value,
        'requested_at'   => now(),
    ]);

    ProcessOptOutJob::dispatchSync();

    expect($log->fresh()->processed_at)->not->toBeNull();
});
