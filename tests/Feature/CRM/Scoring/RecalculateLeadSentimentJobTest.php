<?php

declare(strict_types=1);

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Events\CRM\LeadSentimentFlaggedEvent;
use App\Jobs\CRM\RecalculateLeadSentimentJob;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\SentimentFlag;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('creates sentiment snapshot from inbound communication and dispatches event', function (): void {
    Event::fake([LeadSentimentFlaggedEvent::class]);

    $institution = Institution::create([
        'name' => 'Sentiment Test Institute',
        'code' => 'STI',
        'is_active' => true,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Ari',
        'last_name' => 'Das',
        'mobile' => '9876500088',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 57,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    CommunicationLog::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'channel' => CommunicationChannel::WHATSAPP,
        'direction' => MessageDirection::INBOUND,
        'status' => MessageStatus::DELIVERED,
        'body_preview' => 'I am frustrated and this is urgent, please respond now.',
    ]);

    RecalculateLeadSentimentJob::dispatchSync($lead->uuid);

    $flag = SentimentFlag::withoutGlobalScopes()
        ->where('lead_id', $lead->id)
        ->latest('flagged_at')
        ->first();

    expect($flag)->not->toBeNull();
    expect($flag->sentiment_label?->value)->toBe('negative');
    expect($flag->is_urgent)->toBeTrue();

    Event::assertDispatched(LeadSentimentFlaggedEvent::class);
});
