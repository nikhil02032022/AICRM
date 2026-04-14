<?php

declare(strict_types=1);

// BRD: AG-008 — Agent Bulk Communications: send dispatches job, job records delivery, opt-out respected

use App\Enums\CRM\AgentCommsChannel;
use App\Events\CRM\AgentBulkCommsSentEvent;
use App\Jobs\CRM\SendAgentBulkCommsJob;
use App\Models\CRM\AgentCommsLog;
use App\Models\CRM\Institution;
use App\Models\User;
use App\Services\CRM\Agent\AgentCommsService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeAgentCommsContext(): array
{
    $institution = Institution::create([
        'name' => 'Agent Comms Uni', 'code' => 'ACU1', 'is_active' => true,
    ]);

    $sender = User::create([
        'name' => 'Comms Manager',
        'email' => 'comms@manager.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $sender->givePermissionTo(['crm.agents.comms.send', 'crm.agents.comms.view']);

    $agent1 = User::create([
        'name' => 'Agent One',
        'email' => 'agent1@test.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $agent2 = User::create([
        'name' => 'Agent Two',
        'email' => 'agent2@test.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    return [$institution, $sender, $agent1, $agent2];
}

// ─── Agent Comms: send creates log and dispatches job ────────────────────

test('send creates AgentCommsLog and dispatches SendAgentBulkCommsJob (AG-008)', function (): void {
    Queue::fake();

    [$institution, $sender, $agent1, $agent2] = makeAgentCommsContext();

    $service = app(AgentCommsService::class);

    $log = $service->send($institution->id, $sender->id, [
        'channel' => AgentCommsChannel::Email,
        'subject' => 'Q1 Commission Update',
        'message_body' => 'Please find your Q1 commission summary attached.',
        'recipient_agent_ids' => [$agent1->id, $agent2->id],
        'recipient_count' => 2,
    ]);

    expect($log)->toBeInstanceOf(AgentCommsLog::class)
        ->and($log->channel)->toBe(AgentCommsChannel::Email)
        ->and($log->recipient_count)->toBe(2)
        ->and($log->opt_out_respected)->toBeTrue();

    Queue::assertPushed(SendAgentBulkCommsJob::class);
});

// ─── Agent Comms: recordDelivery updates counts and fires event ───────────

test('recordDelivery updates delivered/failed counts and fires AgentBulkCommsSentEvent (AG-008)', function (): void {
    Event::fake([AgentBulkCommsSentEvent::class]);

    [$institution, $sender, $agent1, $agent2] = makeAgentCommsContext();

    $service = app(AgentCommsService::class);

    $log = AgentCommsLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'sender_user_id' => $sender->id,
        'channel' => AgentCommsChannel::WhatsApp,
        'message_body' => 'New leads available in your portal.',
        'recipient_agent_ids' => [$agent1->id, $agent2->id],
        'recipient_count' => 2,
        'delivered_count' => 0,
        'failed_count' => 0,
        'opt_out_respected' => true,
    ]);

    $service->recordDelivery($log, deliveredCount: 2, failedCount: 0);

    $log->refresh();

    expect($log->delivered_count)->toBe(2)
        ->and($log->failed_count)->toBe(0);

    Event::assertDispatched(AgentBulkCommsSentEvent::class);
});

// ─── Agent Comms: opt_out_respected is always true ────────────────────────

test('opt_out_respected is enforced on every AgentCommsLog (AG-008 DPDP)', function (): void {
    Queue::fake();

    [$institution, $sender, $agent1, $agent2] = makeAgentCommsContext();

    $service = app(AgentCommsService::class);

    $log = $service->send($institution->id, $sender->id, [
        'channel' => AgentCommsChannel::Sms,
        'message_body' => 'Reminder: portal update available.',
        'recipient_agent_ids' => [$agent1->id],
        'recipient_count' => 1,
    ]);

    expect($log->opt_out_respected)->toBeTrue();
});

// ─── Agent Comms: institution scope enforced ──────────────────────────────

test('AgentCommsLog list is scoped to institution (AG-008)', function (): void {
    [$institution, $sender, $agent1, $agent2] = makeAgentCommsContext();

    $otherInstitution = Institution::create([
        'name' => 'Other Uni', 'code' => 'OTH2', 'is_active' => true,
    ]);

    AgentCommsLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'sender_user_id' => $sender->id,
        'channel' => AgentCommsChannel::Email,
        'message_body' => 'Msg A',
        'recipient_agent_ids' => [$agent1->id],
        'recipient_count' => 1,
        'delivered_count' => 0,
        'failed_count' => 0,
        'opt_out_respected' => true,
    ]);

    AgentCommsLog::withoutGlobalScopes()->create([
        'institution_id' => $otherInstitution->id,
        'sender_user_id' => $sender->id,
        'channel' => AgentCommsChannel::Sms,
        'message_body' => 'Msg B',
        'recipient_agent_ids' => [$agent2->id],
        'recipient_count' => 1,
        'delivered_count' => 1,
        'failed_count' => 0,
        'opt_out_respected' => true,
    ]);

    $service = app(AgentCommsService::class);
    $results = $service->list($institution->id);

    expect($results->total())->toBe(1);
});
