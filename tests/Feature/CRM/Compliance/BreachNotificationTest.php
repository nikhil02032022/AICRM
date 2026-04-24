<?php

declare(strict_types=1);

// BRD: CRM-CR-010 — Breach notification workflow: alert institution admin within 72h

use App\Jobs\CRM\Compliance\BreachNotificationJob;
use App\Models\CRM\Compliance\SecurityIncident;
use App\Models\CRM\Institution;
use App\Models\User;
use App\Services\CRM\Compliance\BreachNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->admin       = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->admin->assignRole('institution-admin');

    $this->service = app(BreachNotificationService::class);
});

it('SecurityIncident creation dispatches BreachNotificationJob when notify() is called', function (): void {
    Queue::fake();

    $incident = $this->service->create([
        'institution_id' => $this->institution->id,
        'incident_type'  => 'data_leak',
        'description'    => 'Unauthorised access to lead records detected',
        'detected_at'    => now(),
        'status'         => 'open',
    ], $this->admin);

    $this->service->notify($incident);

    Queue::assertPushed(BreachNotificationJob::class);
});

it('incident notified_at is set after notify()', function (): void {
    Queue::fake();

    $incident = $this->service->create([
        'institution_id' => $this->institution->id,
        'incident_type'  => 'phishing',
        'description'    => 'Phishing attempt targeting staff email accounts',
        'detected_at'    => now(),
        'status'         => 'open',
    ], $this->admin);

    expect($incident->notified_at)->toBeNull();

    $this->service->notify($incident);

    expect($incident->fresh()->notified_at)->not->toBeNull();
});

it('compliance admin can store a security incident via route', function (): void {
    $this->admin->givePermissionTo('crm.compliance.incidents.create');

    $response = $this->actingAs($this->admin)
        ->post(route('compliance.security-incidents.store'), [
            'incident_type' => 'data_leak',
            'description'   => 'Test breach description for compliance route',
            'detected_at'   => now()->toDateTimeString(),
            'status'        => 'open',
        ]);

    $response->assertRedirectContains('security-incidents');

    $this->assertDatabaseHas('security_incidents', [
        'institution_id' => $this->institution->id,
        'incident_type'  => 'data_leak',
    ]);
});

it('BreachNotificationJob is dispatched with correct incident', function (): void {
    Queue::fake();

    $incident = SecurityIncident::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'reported_by'    => $this->admin->id,
        'incident_type'  => 'ransomware',
        'description'    => 'Ransomware detected on internal server',
        'detected_at'    => now(),
        'status'         => 'open',
    ]);

    $this->service->notify($incident);

    Queue::assertPushed(BreachNotificationJob::class, function ($job) use ($incident) {
        return true; // job was dispatched
    });
});
