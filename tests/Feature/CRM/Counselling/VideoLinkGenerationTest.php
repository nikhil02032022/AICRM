<?php

declare(strict_types=1);

// BRD: CRM-EC-018 — Feature tests for meeting link generation on session creation / trigger
use App\Enums\CRM\Counselling\VideoProvider;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\SessionType;
use App\Jobs\CRM\Counselling\SendSessionVideoLinkJob;
use App\Models\CRM\Admin\SystemConfig;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Counselling\VideoMeetingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    $this->institution = Institution::create([
        'name' => 'Test Uni',
        'code' => 'TU01',
        'is_active' => true,
    ]);

    $this->counsellor = User::factory()->create([
        'institution_id' => $this->institution->id,
    ]);

    $this->lead = Lead::withoutGlobalScopes()->create([
        'uuid' => Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name' => 'Priya',
        'mobile' => '9876543210',
        'source' => LeadSource::WALK_IN->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => false,
    ]);

    $this->session = CounsellingSession::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'lead_id' => $this->lead->id,
        'counsellor_id' => $this->counsellor->id,
        'session_type' => SessionType::INITIAL->value,
        'status' => \App\Enums\CRM\CounsellingSessionStatus::SCHEDULED->value,
        'mode' => 'online',
        'scheduled_at' => now()->addDay(),
    ]);
});

it('generateLink sets meeting_link and meeting_provider on the session', function (): void {
    Queue::fake();

    SystemConfig::create([
        'institution_id' => $this->institution->id,
        'key' => 'zoom_room_url',
        'value' => 'https://zoom.us/j/test',
        'type' => 'string',
    ]);

    config(['crm_video.provider' => 'zoom']);

    app(VideoMeetingService::class)->generateLink($this->session);

    $this->session->refresh();
    expect($this->session->meeting_link)->toBe('https://zoom.us/j/test');
    expect($this->session->meeting_provider)->toBe(VideoProvider::ZOOM);
});

it('SendSessionVideoLinkJob is dispatched after link generation', function (): void {
    Queue::fake();

    SystemConfig::create([
        'institution_id' => $this->institution->id,
        'key' => 'zoom_room_url',
        'value' => 'https://zoom.us/j/test2',
        'type' => 'string',
    ]);

    config(['crm_video.provider' => 'zoom']);

    app(VideoMeetingService::class)->generateLink($this->session);

    Queue::assertPushed(SendSessionVideoLinkJob::class, fn ($job) => $job->sessionId === $this->session->getKey());
});

it('no job dispatched when provider is none', function (): void {
    Queue::fake();
    config(['crm_video.provider' => 'none']);

    app(VideoMeetingService::class)->generateLink($this->session);

    Queue::assertNothingPushed();
    $this->session->refresh();
    expect($this->session->meeting_link)->toBeNull();
});
