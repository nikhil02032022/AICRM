<?php

declare(strict_types=1);

// BRD: CRM-EC-019 — Unit tests for WalkInQueueService token lifecycle operations
use App\Enums\CRM\Counselling\WalkInTokenStatus;
use App\Events\CRM\Counselling\WalkInTokenCalled;
use App\Events\CRM\Counselling\WalkInTokenStatusChanged;
use App\Models\CRM\Campus;
use App\Models\CRM\Institution;
use App\Models\CRM\WalkInToken;
use App\Models\User;
use App\Services\CRM\Counselling\WalkInQueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
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

it('issues a token with sequential token_number starting at 1', function (): void {
    Event::fake([WalkInTokenStatusChanged::class]);

    $token = $this->svc->issueToken($this->campus, []);

    expect($token->token_number)->toBe(1);
    expect($token->status)->toBe(WalkInTokenStatus::WAITING);
    expect($token->campus_id)->toBe($this->campus->id);
});

it('increments token_number for subsequent tokens today', function (): void {
    Event::fake([WalkInTokenStatusChanged::class, WalkInTokenCalled::class]);

    $t1 = $this->svc->issueToken($this->campus, []);
    $t2 = $this->svc->issueToken($this->campus, []);
    $t3 = $this->svc->issueToken($this->campus, []);

    expect($t1->token_number)->toBe(1);
    expect($t2->token_number)->toBe(2);
    expect($t3->token_number)->toBe(3);
});

it('callNext picks the lowest waiting token_number', function (): void {
    Event::fake([WalkInTokenStatusChanged::class, WalkInTokenCalled::class]);

    $t1 = $this->svc->issueToken($this->campus, []);
    $t2 = $this->svc->issueToken($this->campus, []);

    $called = $this->svc->callNext($this->campus, $this->counsellor);

    expect($called->token_number)->toBe($t1->token_number);
    expect($called->status)->toBe(WalkInTokenStatus::CALLED);
    expect($called->counsellor_id)->toBe($this->counsellor->id);
    expect($called->called_at)->not->toBeNull();
});

it('serve marks token as Served with served_at timestamp', function (): void {
    Event::fake([WalkInTokenStatusChanged::class, WalkInTokenCalled::class]);

    $token = $this->svc->issueToken($this->campus, []);
    $this->svc->callNext($this->campus, $this->counsellor);

    $token->refresh();
    $this->svc->serve($token);

    $token->refresh();
    expect($token->status)->toBe(WalkInTokenStatus::SERVED);
    expect($token->served_at)->not->toBeNull();
});

it('skip marks token as Skipped with skipped_at timestamp', function (): void {
    Event::fake([WalkInTokenStatusChanged::class, WalkInTokenCalled::class]);

    $token = $this->svc->issueToken($this->campus, []);
    $this->svc->callNext($this->campus, $this->counsellor);

    $token->refresh();
    $this->svc->skip($token);

    $token->refresh();
    expect($token->status)->toBe(WalkInTokenStatus::SKIPPED);
    expect($token->skipped_at)->not->toBeNull();
});

it('dailyStats returns correct totals', function (): void {
    Event::fake([WalkInTokenStatusChanged::class, WalkInTokenCalled::class]);

    $this->svc->issueToken($this->campus, []);
    $this->svc->issueToken($this->campus, []);
    $token = $this->svc->issueToken($this->campus, []);

    $called = $this->svc->callNext($this->campus, $this->counsellor);
    $called->refresh();
    $this->svc->serve($called);

    $stats = $this->svc->dailyStats($this->campus);

    expect($stats['total'])->toBe(3);
    expect($stats['served'])->toBe(1);
    expect($stats['waiting'])->toBe(2);
    expect($stats['skipped'])->toBe(0);
});
