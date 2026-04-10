<?php

declare(strict_types=1);

// BRD: CRM-CC-006 to CRM-CC-010 — SMS communication: DLT templates, campaigns, delivery, opt-out

use App\Enums\CRM\CampaignStatus;
use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\DltMessageType;
use App\Enums\CRM\DltTemplateStatus;
use App\Enums\CRM\SmsGateway;
use App\Jobs\CRM\Communication\EnforceUnsubscribeJob;
use App\Jobs\CRM\Communication\ProcessSmsDeliveryJob;
use App\Jobs\CRM\Communication\SendBulkSmsJob;
use App\Models\CRM\DltTemplate;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\SmsCampaign;
use App\Models\User;
use App\Services\CRM\Communication\DltTemplateService;
use App\Services\CRM\Communication\SmsService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->institution = Institution::create([
        'name' => 'SMS Test University', 'code' => 'STU2', 'is_active' => true,
    ]);

    $this->counsellor = User::create([
        'name'           => 'SMS Counsellor',
        'email'          => 'sms-counsellor@stu.com',
        'password'       => bcrypt('password'),
        'institution_id' => $this->institution->id,
    ]);
    $this->counsellor->givePermissionTo(['crm.communication.send', 'crm.leads.view']);

    $this->lead = Lead::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'name'           => 'SMS Student',
        'email'          => 'sms@example.com',
        'mobile'         => '9876543211',
        'consent_given'  => true,
    ]);
});

// ─── DLT Template CRUD ───────────────────────────────────────────────────

it('creates DLT template in DRAFT status', function (): void {
    $this->actingAs($this->counsellor)
        ->post(route('crm.communication.sms.dlt.store'), [
            'template_name' => 'OTP Template',
            'message_type'  => DltMessageType::TRANSACTIONAL->value,
            'sender_id'     => 'MEETCS',
            'template_body' => 'Your OTP is {#var#}. Valid for 10 minutes.',
        ]);

    expect(DltTemplate::where('template_name', 'OTP Template')->first()?->status)
        ->toBe(DltTemplateStatus::DRAFT);
});

it('can submit DLT template for approval', function (): void {
    $template = DltTemplate::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'template_name'  => 'Submit Test',
        'message_type'   => DltMessageType::TRANSACTIONAL,
        'sender_id'      => 'MEETCS',
        'template_body'  => 'Test body {#var#}',
        'status'         => DltTemplateStatus::DRAFT,
    ]);

    app(DltTemplateService::class)->submitForApproval($template);

    expect($template->fresh()->status)->toBe(DltTemplateStatus::PENDING_APPROVAL);
});

it('marks DLT template as approved', function (): void {
    $template = DltTemplate::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'template_name'  => 'Approve Test',
        'message_type'   => DltMessageType::TRANSACTIONAL,
        'sender_id'      => 'MEETCS',
        'template_body'  => 'Approval body {#var#}',
        'status'         => DltTemplateStatus::PENDING_APPROVAL,
        'dlt_template_id' => 'DLT123456',
    ]);

    app(DltTemplateService::class)->markApproved($template);

    expect($template->fresh()->status)->toBe(DltTemplateStatus::APPROVED);
});

it('canSend() only returns true for approved DLT templates', function (): void {
    $draft = DltTemplate::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'template_name'  => 'Draft',
        'message_type'   => DltMessageType::TRANSACTIONAL,
        'sender_id'      => 'MEETCS',
        'template_body'  => 'Body',
        'status'         => DltTemplateStatus::DRAFT,
    ]);

    $approved = DltTemplate::create([
        'uuid'           => \Illuminate\Support\Str::uuid(),
        'institution_id' => $this->institution->id,
        'template_name'  => 'Approved',
        'message_type'   => DltMessageType::TRANSACTIONAL,
        'sender_id'      => 'MEETCS',
        'template_body'  => 'Body',
        'status'         => DltTemplateStatus::APPROVED,
        'dlt_template_id' => 'DLT999',
    ]);

    expect($draft->canSend())->toBeFalse();
    expect($approved->canSend())->toBeTrue();
});

it('lists only approved DLT templates on SMS campaign create page', function (): void {
    DltTemplate::create([
        'uuid' => \Illuminate\Support\Str::uuid(), 'institution_id' => $this->institution->id,
        'template_name' => 'Approved Tpl', 'message_type' => DltMessageType::TRANSACTIONAL,
        'sender_id' => 'MEETCS', 'template_body' => 'Body', 'status' => DltTemplateStatus::APPROVED,
        'dlt_template_id' => 'DLTOK',
    ]);
    DltTemplate::create([
        'uuid' => \Illuminate\Support\Str::uuid(), 'institution_id' => $this->institution->id,
        'template_name' => 'Draft Hidden', 'message_type' => DltMessageType::TRANSACTIONAL,
        'sender_id' => 'MEETCS', 'template_body' => 'Body', 'status' => DltTemplateStatus::DRAFT,
    ]);

    $this->actingAs($this->counsellor)
        ->get(route('crm.communication.sms.campaigns.create'))
        ->assertSee('Approved Tpl')
        ->assertDontSee('Draft Hidden');
});

// ─── SMS Campaign ─────────────────────────────────────────────────────────

it('creates SMS campaign in DRAFT status', function (): void {
    $tpl = DltTemplate::create([
        'uuid' => \Illuminate\Support\Str::uuid(), 'institution_id' => $this->institution->id,
        'template_name' => 'Camp Tpl', 'message_type' => DltMessageType::TRANSACTIONAL,
        'sender_id' => 'MEETCS', 'template_body' => 'Body', 'status' => DltTemplateStatus::APPROVED,
        'dlt_template_id' => 'DLTCAMP',
    ]);

    $this->actingAs($this->counsellor)
        ->post(route('crm.communication.sms.campaigns.store'), [
            'name'            => 'Test SMS Campaign',
            'dlt_template_id' => $tpl->id,
            'gateway'         => SmsGateway::MSG91->value,
        ]);

    expect(SmsCampaign::where('name', 'Test SMS Campaign')->first()?->status)
        ->toBe(CampaignStatus::DRAFT);
});

it('dispatches SendBulkSmsJob on SMS campaign launch', function (): void {
    Queue::fake();

    $tpl = DltTemplate::create([
        'uuid' => \Illuminate\Support\Str::uuid(), 'institution_id' => $this->institution->id,
        'template_name' => 'Launch Tpl', 'message_type' => DltMessageType::TRANSACTIONAL,
        'sender_id' => 'MEETCS', 'template_body' => 'Hello {#var#}', 'status' => DltTemplateStatus::APPROVED,
        'dlt_template_id' => 'DLTLAUNCH',
    ]);
    $campaign = SmsCampaign::create([
        'uuid' => \Illuminate\Support\Str::uuid(), 'institution_id' => $this->institution->id,
        'name' => 'Launch Campaign', 'dlt_template_id' => $tpl->id,
        'gateway' => SmsGateway::MSG91, 'status' => CampaignStatus::DRAFT,
    ]);

    $this->actingAs($this->counsellor)
        ->post(route('crm.communication.sms.campaigns.launch', $campaign->uuid));

    Queue::assertPushedOn('crm-comms-sms', SendBulkSmsJob::class);
});

// ─── SMS Opt-out (DPDP) ───────────────────────────────────────────────────

it('dispatches EnforceUnsubscribeJob on SMS opt-out', function (): void {
    Queue::fake();

    app(SmsService::class)->optOutLead($this->lead);

    Queue::assertPushed(EnforceUnsubscribeJob::class);
});

it('marks lead sms_unsubscribed_at on opt-out', function (): void {
    $job = new EnforceUnsubscribeJob($this->lead->id, 'sms');
    $job->handle(app(\App\Services\CRM\Communication\EmailService::class));

    expect($this->lead->fresh()->sms_unsubscribed_at)->not->toBeNull();
});

// ─── Webhook ─────────────────────────────────────────────────────────────

it('dispatches ProcessSmsDeliveryJob on valid SMS webhook', function (): void {
    Queue::fake();
    config(['services.sms.msg91.webhook_secret' => 'sms-secret']);

    $payload = json_encode(['requestId' => 'abc123', 'status' => 'DELIVERED']);
    $signature = hash_hmac('sha256', $payload, 'sms-secret');

    $this->post(
        route('api.crm.webhooks.sms', ['gateway' => 'msg91']),
        json_decode($payload, true),
        ['X-Webhook-Signature' => $signature]
    )->assertOk();

    Queue::assertPushedOn('crm-comms-sms', ProcessSmsDeliveryJob::class);
});

it('rejects SMS webhook with wrong signature', function (): void {
    config(['services.sms.msg91.webhook_secret' => 'sms-secret']);

    $this->post(
        route('api.crm.webhooks.sms', ['gateway' => 'msg91']),
        ['requestId' => 'xxx'],
        ['X-Webhook-Signature' => 'bad-signature']
    )->assertForbidden();
});

it('returns 404 for unsupported SMS gateway webhook', function (): void {
    $this->post(route('api.crm.webhooks.sms', ['gateway' => 'unknown']))
        ->assertNotFound();
});

// ─── Multi-tenancy ────────────────────────────────────────────────────────

it('enforces institution scoping on DLT templates', function (): void {
    $other = Institution::create(['name' => 'Other SMS Uni', 'code' => 'OSU9', 'is_active' => true]);
    DltTemplate::create([
        'uuid' => \Illuminate\Support\Str::uuid(), 'institution_id' => $other->id,
        'template_name' => 'Other Tpl', 'message_type' => DltMessageType::TRANSACTIONAL,
        'sender_id' => 'OTHER', 'template_body' => 'Other body', 'status' => DltTemplateStatus::APPROVED,
        'dlt_template_id' => 'DLTOTHER',
    ]);

    $this->actingAs($this->counsellor)
        ->get(route('crm.communication.sms.dlt.index'))
        ->assertDontSee('Other Tpl');
});

it('does not send SMS to opted-out lead', function (): void {
    $this->lead->update(['sms_unsubscribed_at' => now()]);

    $result = app(SmsService::class)->sendToLead($this->lead, 'DLT123', 'Hello');

    expect($result)->toBeNull();
});
