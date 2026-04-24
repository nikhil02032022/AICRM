<?php

declare(strict_types=1);

// BRD: CRM-CR-008 — SMS communications must use DLT-registered templates

use App\Enums\CRM\Admin\NotificationChannel;
use App\Models\CRM\Admin\NotificationTemplate;
use App\Models\CRM\Institution;
use App\Models\User;
use App\Services\CRM\Compliance\DltTemplateValidatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->user        = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->service     = app(DltTemplateValidatorService::class);
});

it('isRegistered() returns true for existing SMS template', function (): void {
    NotificationTemplate::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'channel'        => NotificationChannel::SMS->value,
        'name'           => 'OTP Template',
        'body'           => 'Your OTP is {{code}}',
        'is_active'      => true,
    ]);

    $result = $this->service->isRegistered('Your OTP is {{code}}');

    expect($result)->toBeTrue();
});

it('isRegistered() returns false for unregistered content', function (): void {
    $result = $this->service->isRegistered('Random message not in DB');

    expect($result)->toBeFalse();
});

it('isRegistered() returns false for inactive SMS template', function (): void {
    NotificationTemplate::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'channel'        => NotificationChannel::SMS->value,
        'name'           => 'Inactive Template',
        'body'           => 'Your application status has been updated',
        'is_active'      => false,
    ]);

    $result = $this->service->isRegistered('Your application status has been updated');

    expect($result)->toBeFalse();
});

it('validate() returns false for content exceeding 160 chars', function (): void {
    NotificationTemplate::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'channel'        => NotificationChannel::SMS->value,
        'name'           => 'Long Template',
        'body'           => str_repeat('A', 50),
        'is_active'      => true,
    ]);

    $longContent = str_repeat('A', 161);
    $result      = $this->service->validate($longContent, 'SENDER1');

    expect($result)->toBeFalse();
});
