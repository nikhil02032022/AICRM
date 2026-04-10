<?php

declare(strict_types=1);

// BRD: CRM-CC-016 to CRM-CC-020, CRM-LC-010 — Voice/IVR: click-to-call, call log, IVR, auto-lead

use App\Enums\CRM\CallDirection;
use App\Enums\CRM\CallDisposition;
use App\Enums\CRM\CallStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\TelephonyProvider;
use App\Events\CRM\Communication\CallCompletedEvent;
use App\Events\CRM\Communication\CallInitiatedEvent;
use App\Events\CRM\Communication\IvrLeadCreatedEvent;
use App\Events\CRM\Communication\MissedCallReceivedEvent;
use App\Jobs\CRM\Communication\ProcessIvrLeadCreationJob;
use App\Jobs\CRM\Communication\ProcessOutboundCallJob;
use App\Jobs\CRM\Communication\ProcessTelephonyWebhookJob;
use App\Models\CRM\CallLog;
use App\Models\CRM\Institution;
use App\Models\CRM\IvrConfig;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Communication\IvrService;
use App\Services\CRM\Communication\VoiceService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->institution = Institution::create([
        'name' => 'Voice Test University', 'code' => 'VTU5', 'is_active' => true,
    ]);

    $this->counsellor = User::create([
        'name'           => 'Voice Counsellor',
        'email'          => 'voice@vtu.com',
        'password'       => bcrypt('password'),
        'institution_id' => $this->institution->id,
    ]);
    $this->counsellor->givePermissionTo(['crm.communication.send', 'crm.settings.manage', 'crm.leads.view']);

    $this->lead = Lead::create([
        'uuid'                => \Illuminate\Support\Str::uuid(),
        'institution_id'      => $this->institution->id,
        'name'                => 'Voice Student',
        'email'               => 'voice@example.com',
        'mobile'              => '9876543213',
        'consent_given'       => true,
        'call_consent_given'  => true,
    ]);
});

// ─── Click-to-Call ────────────────────────────────────────────────────────

it('initiates click-to-call and creates CallLog', function (): void {
    $callLog = app(VoiceService::class)->initiateClickToCall($this->lead, $this->counsellor);

    expect($callLog)->toBeInstanceOf(CallLog::class);
    expect($callLog->direction)->toBe(CallDirection::OUTBOUND);
    expect($callLog->status)->toBe(CallStatus::INITIATED);
});

it('dispatches ProcessOutboundCallJob on click-to-call', function (): void {
    Queue::fake();

    $this->actingAs($this->counsellor)
        ->post(route('crm.communication.voice.leads.call', $this->lead->uuid));

    Queue::assertPushedOn('crm-comms-voice', ProcessOutboundCallJob::class);
});

it('fires CallInitiatedEvent on click-to-call', function (): void {
    Event::fake([CallInitiatedEvent::class]);

    app(VoiceService::class)->initiateClickToCall($this->lead, $this->counsellor);

    Event::assertDispatched(CallInitiatedEvent::class);
});

// ─── Call Disposition ─────────────────────────────────────────────────────

it('can record call disposition via web', function (): void {
    $callLog = CallLog::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'lead_id'        => $this->lead->id,
        'initiated_by'   => $this->counsellor->id,
        'direction'      => CallDirection::OUTBOUND,
        'status'         => CallStatus::COMPLETED,
    ]);

    $this->actingAs($this->counsellor)
        ->post(route('crm.communication.voice.calls.disposition', $callLog->uuid), [
            'disposition'       => CallDisposition::INTERESTED->value,
            'disposition_notes' => 'Student showed interest in MBA',
            'duration_seconds'  => 180,
        ]);

    expect($callLog->fresh()->disposition)->toBe(CallDisposition::INTERESTED);
});

it('fires CallCompletedEvent on finalise', function (): void {
    Event::fake([CallCompletedEvent::class]);

    $callLog = CallLog::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'lead_id'        => $this->lead->id,
        'initiated_by'   => $this->counsellor->id,
        'direction'      => CallDirection::OUTBOUND,
        'status'         => CallStatus::COMPLETED,
    ]);

    app(VoiceService::class)->finaliseCallLog($callLog, [
        'disposition'      => CallDisposition::NOT_INTERESTED->value,
        'duration_seconds' => 60,
    ]);

    Event::assertDispatched(CallCompletedEvent::class);
});

// ─── Call Recording DPDP Gate ─────────────────────────────────────────────

it('refuses to attach recording when call_consent_given is false', function (): void {
    $this->lead->update(['call_consent_given' => false]);

    $callLog = CallLog::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'lead_id'        => $this->lead->id,
        'initiated_by'   => $this->counsellor->id,
        'direction'      => CallDirection::OUTBOUND,
        'status'         => CallStatus::COMPLETED,
    ]);

    $result = app(VoiceService::class)->attachRecording($callLog, 's3://recordings/test.mp3');

    expect($result)->toBeFalse();
    expect($callLog->fresh()->recording_url)->toBeNull();
});

it('attaches recording when call_consent_given is true', function (): void {
    $callLog = CallLog::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'lead_id'        => $this->lead->id,
        'initiated_by'   => $this->counsellor->id,
        'direction'      => CallDirection::OUTBOUND,
        'status'         => CallStatus::COMPLETED,
    ]);

    $result = app(VoiceService::class)->attachRecording($callLog, 's3://recordings/test.mp3');

    expect($result)->toBeTrue();
    expect($callLog->fresh()->recording_url)->toBe('s3://recordings/test.mp3');
});

// ─── Telephony Webhook ────────────────────────────────────────────────────

it('dispatches ProcessTelephonyWebhookJob from allowed IP', function (): void {
    Queue::fake();
    config(['services.telephony.allowed_ips' => ['127.0.0.1']]);

    $this->post(
        route('api.crm.webhooks.telephony', ['provider' => 'exotel']),
        ['call_id' => 'exo123', 'status' => 'completed']
    )->assertOk();

    Queue::assertPushedOn('crm-comms-voice', ProcessTelephonyWebhookJob::class);
});

it('rejects telephony webhook from non-allowed IP', function (): void {
    config(['services.telephony.allowed_ips' => ['10.0.0.1']]);

    $this->post(
        route('api.crm.webhooks.telephony', ['provider' => 'exotel']),
        ['call_id' => 'exo456']
    )->assertForbidden();
});

// ─── IVR Config ───────────────────────────────────────────────────────────

it('can create IVR config via settings', function (): void {
    $this->actingAs($this->counsellor)
        ->post(route('crm.settings.ivr.store'), [
            'provider'            => TelephonyProvider::EXOTEL->value,
            'virtual_number'      => '08040000000',
            'welcome_message'     => 'Press 1 for MBA admissions',
            'collect_name'        => true,
            'collect_programme'   => true,
            'is_active'           => true,
        ]);

    expect(IvrConfig::count())->toBe(1);
});

it('can toggle IVR config active status', function (): void {
    $config = IvrConfig::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'provider'       => TelephonyProvider::EXOTEL,
        'virtual_number' => '08040000000',
        'is_active'      => true,
    ]);

    $this->actingAs($this->counsellor)
        ->post(route('crm.settings.ivr.toggle', $config->uuid));

    expect($config->fresh()->is_active)->toBeFalse();
});

// ─── IVR Auto-lead (LC-010) ───────────────────────────────────────────────

it('auto-creates lead from IVR inbound call (LC-010)', function (): void {
    Event::fake([IvrLeadCreatedEvent::class]);

    $config = IvrConfig::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'provider'       => TelephonyProvider::EXOTEL,
        'virtual_number' => '08040000000',
        'is_active'      => true,
    ]);

    app(IvrService::class)->handleInboundIvrCall([
        'institution_id' => $this->institution->id,
        'call_id'        => 'ivr-call-001',
        'caller_number'  => '+919500000001',
        'ivr_config_id'  => $config->id,
        'collected_name' => 'IVR Student',
    ]);

    $lead = Lead::where('mobile', '9500000001')->first();
    expect($lead)->not->toBeNull();
    expect($lead?->source)->toBe(LeadSource::IVR);
    Event::assertDispatched(IvrLeadCreatedEvent::class);
});

it('dispatches ProcessIvrLeadCreationJob on IVR webhook', function (): void {
    Queue::fake();
    config(['services.telephony.allowed_ips' => ['127.0.0.1']]);

    $this->post(
        route('api.crm.webhooks.ivr', ['provider' => 'exotel']),
        ['call_sid' => 'ivr-sid-001', 'caller' => '09800000001']
    )->assertOk();

    Queue::assertPushedOn('crm-comms-voice', ProcessIvrLeadCreationJob::class);
});

it('fires MissedCallReceivedEvent when call is not answered', function (): void {
    Event::fake([MissedCallReceivedEvent::class]);

    $callLog = CallLog::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'lead_id'        => $this->lead->id,
        'direction'      => CallDirection::INBOUND,
        'status'         => CallStatus::NO_ANSWER,
    ]);

    app(VoiceService::class)->handleProviderEvent($callLog, 'no-answer', []);

    Event::assertDispatched(MissedCallReceivedEvent::class);
});

// ─── Call Log encryption (DPDP) ───────────────────────────────────────────

it('encrypts call log phone numbers at rest', function (): void {
    $callLog = CallLog::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'lead_id'        => $this->lead->id,
        'direction'      => CallDirection::OUTBOUND,
        'status'         => CallStatus::INITIATED,
        'from_number'    => '08040000001',
        'to_number'      => '9876543213',
    ]);

    $rawFrom = \DB::table('call_logs')->where('id', $callLog->id)->value('from_number');
    expect($rawFrom)->not->toBe('08040000001');
    expect($callLog->from_number)->toBe('08040000001');
});
