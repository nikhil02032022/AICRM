<?php

declare(strict_types=1);

// BRD: CRM-EC-019 — Feature tests for walk-in queue broadcast events
use App\Enums\CRM\Counselling\WalkInTokenStatus;
use App\Events\CRM\Counselling\WalkInTokenCalled;
use App\Events\CRM\Counselling\WalkInTokenStatusChanged;
use App\Models\CRM\Campus;
use App\Models\CRM\Institution;
use App\Models\CRM\WalkInToken;
use App\Models\User;
use App\Services\CRM\Counselling\WalkInQueueService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    $this->institution = Institution::create([
        'name' => 'Test Uni',
        'code' => 'TU01',
        'is_active' => true,
    ]);

    $this->campus = Campus::create([
        'institution_id' => $this->institution->id,
        'name' => 'Main Campus',
        'code' => 'MC01',
        'is_active' => true,
    ]);

    $this->counsellor = User::factory()->create([
        'institution_id' => $this->institution->id,
        'campus_id' => $this->campus->id,
    ]);

    $this->svc = app(WalkInQueueService::class);
});

it('callNext dispatches WalkInTokenCalled broadcast event', function (): void {
    Event::fake([WalkInTokenCalled::class, WalkInTokenStatusChanged::class]);

    $this->svc->issueToken($this->campus, []);
    $this->svc->callNext($this->campus, $this->counsellor);

    Event::assertDispatched(WalkInTokenCalled::class, function (WalkInTokenCalled $event): bool {
        return $event->token->status === WalkInTokenStatus::CALLED;
    });
});

it('serve dispatches WalkInTokenStatusChanged broadcast event', function (): void {
    Event::fake([WalkInTokenCalled::class, WalkInTokenStatusChanged::class]);

    $this->svc->issueToken($this->campus, []);
    $called = $this->svc->callNext($this->campus, $this->counsellor);
    $called->refresh();
    $this->svc->serve($called);

    Event::assertDispatched(WalkInTokenStatusChanged::class, function (WalkInTokenStatusChanged $e): bool {
        return $e->token->status === WalkInTokenStatus::SERVED;
    });
});

it('broadcast payload contains token_number and status', function (): void {
    Event::fake([WalkInTokenCalled::class, WalkInTokenStatusChanged::class]);

    $this->svc->issueToken($this->campus, []);
    $this->svc->callNext($this->campus, $this->counsellor);

    Event::assertDispatched(WalkInTokenCalled::class, function (WalkInTokenCalled $e): bool {
        $payload = $e->broadcastWith();
        return isset($payload['token_number'], $payload['status'])
            && $payload['status'] === WalkInTokenStatus::CALLED->value;
    });
});
