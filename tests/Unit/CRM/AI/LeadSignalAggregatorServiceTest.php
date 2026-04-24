<?php

declare(strict_types=1);

// BRD: CRM-AI-001 — Unit tests for LeadSignalAggregatorService

use App\Enums\CRM\MessageDirection;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\AI\LeadSignalAggregatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();
    $this->service     = app(LeadSignalAggregatorService::class);
});

it('aggregates inbound communication frequency from communication logs', function (): void {
    $lead = Lead::factory()->create(['institution_id' => $this->institution->id]);

    CommunicationLog::factory()->count(3)->create([
        'institution_id' => $this->institution->id,
        'lead_id'        => $lead->id,
        'direction'      => MessageDirection::INBOUND->value,
        'created_at'     => now()->subDays(10),
    ]);

    CommunicationLog::factory()->count(2)->create([
        'institution_id' => $this->institution->id,
        'lead_id'        => $lead->id,
        'direction'      => MessageDirection::OUTBOUND->value,
        'created_at'     => now()->subDays(5),
    ]);

    $signals = $this->service->aggregate($lead);

    expect($signals['inbound_message_count'])->toBe(3);
});

it('caps signal window at 90 days and excludes older communications', function (): void {
    $lead = Lead::factory()->create(['institution_id' => $this->institution->id]);

    // Recent inbound — within window
    CommunicationLog::factory()->count(2)->create([
        'institution_id' => $this->institution->id,
        'lead_id'        => $lead->id,
        'direction'      => MessageDirection::INBOUND->value,
        'created_at'     => now()->subDays(30),
    ]);

    // Old inbound — outside 90-day window
    CommunicationLog::factory()->count(5)->create([
        'institution_id' => $this->institution->id,
        'lead_id'        => $lead->id,
        'direction'      => MessageDirection::INBOUND->value,
        'created_at'     => now()->subDays(100),
    ]);

    $signals = $this->service->aggregate($lead);

    expect($signals['inbound_message_count'])->toBe(2);
});

it('returns zero counselling session count for a new lead with no sessions', function (): void {
    $lead = Lead::factory()->create(['institution_id' => $this->institution->id]);

    $signals = $this->service->aggregate($lead);

    expect($signals['counselling_session_count'])->toBe(0);
    expect($signals['programme_interest_count'])->toBe(0);
    expect($signals['questionnaire_completed'])->toBeFalse();
});

it('returns correct source quality score from lead source mapping', function (): void {
    $referralLead = Lead::factory()->create([
        'institution_id' => $this->institution->id,
        'source'         => 'referral',
    ]);

    $csvLead = Lead::factory()->create([
        'institution_id' => $this->institution->id,
        'source'         => 'csv_import',
    ]);

    $referralSignals = $this->service->aggregate($referralLead);
    $csvSignals      = $this->service->aggregate($csvLead);

    // Referral should have higher quality than CSV import
    expect($referralSignals['source_quality_score'])->toBeGreaterThan($csvSignals['source_quality_score']);
    expect($referralSignals['source_quality_score'])->toBe(0.90);
    expect($csvSignals['source_quality_score'])->toBe(0.40);
});
