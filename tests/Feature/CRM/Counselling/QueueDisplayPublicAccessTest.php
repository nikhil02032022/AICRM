<?php

declare(strict_types=1);

// BRD: CRM-EC-019 — Feature tests for public TV display screen access and data safety
use App\Enums\CRM\Counselling\WalkInTokenStatus;
use App\Models\CRM\Campus;
use App\Models\CRM\Institution;
use App\Models\CRM\WalkInToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
});

it('public display route is accessible without authentication', function (): void {
    $response = $this->get(route('public.queue.display', $this->institution->uuid));

    $response->assertOk();
});

it('display screen does not expose visitor names or mobile numbers', function (): void {
    WalkInToken::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'campus_id' => $this->campus->id,
        'token_number' => 1,
        'token_date' => Carbon::today()->toDateString(),
        'visitor_name' => 'Secret Visitor',
        'visitor_mobile' => '9876543210',
        'status' => WalkInTokenStatus::CALLED->value,
        'called_at' => now(),
    ]);

    $response = $this->get(route('public.queue.display', $this->institution->uuid));

    $response->assertOk()
        ->assertDontSee('Secret Visitor')
        ->assertDontSee('9876543210');
});
