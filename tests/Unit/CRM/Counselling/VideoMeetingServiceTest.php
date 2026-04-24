<?php

declare(strict_types=1);

// BRD: CRM-EC-018 — Unit tests for VideoMeetingService provider resolution and fallback
use App\Enums\CRM\Counselling\VideoProvider;
use App\Jobs\CRM\Counselling\SendSessionVideoLinkJob;
use App\Models\CRM\Admin\SystemConfig;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Institution;
use App\Services\CRM\Counselling\VideoMeeting\GoogleMeetProvider;
use App\Services\CRM\Counselling\VideoMeeting\WebRtcProvider;
use App\Services\CRM\Counselling\VideoMeeting\ZoomProvider;
use App\Services\CRM\Counselling\VideoMeetingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::create([
        'name' => 'Test Uni',
        'code' => 'TU01',
        'is_active' => true,
    ]);

    $this->session = CounsellingSession::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'lead_id' => \App\Models\CRM\Lead::withoutGlobalScopes()->create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'institution_id' => $this->institution->id,
            'first_name' => 'Anita',
            'mobile' => '9876543210',
            'source' => \App\Enums\CRM\LeadSource::WALK_IN->value,
            'status' => \App\Enums\CRM\LeadStatus::NEW_ENQUIRY->value,
            'consent_given' => false,
        ])->id,
        'counsellor_id' => \App\Models\User::factory()->create([
            'institution_id' => $this->institution->id,
        ])->id,
        'session_type' => \App\Enums\CRM\SessionType::INITIAL->value,
        'status' => \App\Enums\CRM\CounsellingSessionStatus::SCHEDULED->value,
        'mode' => 'online',
        'scheduled_at' => now()->addDay(),
    ]);
});

it('generates meeting link via Zoom provider and persists on session', function (): void {
    Queue::fake();

    SystemConfig::create([
        'institution_id' => $this->institution->id,
        'key' => 'zoom_room_url',
        'value' => 'https://zoom.us/j/123456789',
        'type' => 'string',
    ]);

    config(['crm_video.provider' => 'zoom']);

    $svc  = app(VideoMeetingService::class);
    $link = $svc->generateLink($this->session);

    expect($link)->toBe('https://zoom.us/j/123456789');

    $this->session->refresh();
    expect($this->session->meeting_link)->toBe('https://zoom.us/j/123456789');
    expect($this->session->meeting_provider)->toBe(VideoProvider::ZOOM);
});

it('dispatches SendSessionVideoLinkJob after persisting link', function (): void {
    Queue::fake();

    SystemConfig::create([
        'institution_id' => $this->institution->id,
        'key' => 'zoom_room_url',
        'value' => 'https://zoom.us/j/999',
        'type' => 'string',
    ]);

    config(['crm_video.provider' => 'zoom']);

    app(VideoMeetingService::class)->generateLink($this->session);

    Queue::assertPushed(SendSessionVideoLinkJob::class, fn ($job) => $job->sessionId === $this->session->getKey());
});

it('returns null when provider is none', function (): void {
    Queue::fake();
    config(['crm_video.provider' => 'none']);

    $link = app(VideoMeetingService::class)->generateLink($this->session);

    expect($link)->toBeNull();
    Queue::assertNothingPushed();
});

it('falls back to Zoom when Google Meet credentials are missing', function (): void {
    Queue::fake();

    SystemConfig::create([
        'institution_id' => $this->institution->id,
        'key' => 'zoom_room_url',
        'value' => 'https://zoom.us/j/fallback',
        'type' => 'string',
    ]);

    config(['crm_video.provider' => 'google_meet']);

    // No IntegrationCredential for google_meet — GoogleMeetProvider throws → falls back to Zoom
    $link = app(VideoMeetingService::class)->generateLink($this->session);

    expect($link)->toBe('https://zoom.us/j/fallback');
});

it('WebRtc provider returns a route URL', function (): void {
    $provider = app(WebRtcProvider::class);
    $url      = $provider->generateLink($this->session);

    expect($url)->toBeString()->not->toBeEmpty();
});
