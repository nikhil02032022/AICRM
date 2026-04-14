<?php

declare(strict_types=1);

// BRD: AG-006 — Agent Commission: create, approve, reject, mark paid workflows

use App\Enums\CRM\CommissionStatus;
use App\Events\CRM\AgentCommissionApprovedEvent;
use App\Jobs\CRM\ProcessAgentCommissionJob;
use App\Models\CRM\AgentCommission;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Agent\AgentCommissionService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeCommissionContext(): array
{
    $institution = Institution::create([
        'name' => 'Commission Uni', 'code' => 'CMU1', 'is_active' => true,
    ]);

    $admin = User::create([
        'name' => 'Commission Admin',
        'email' => 'comm@admin.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $admin->givePermissionTo(['crm.agents.commissions.manage', 'crm.agents.commissions.view']);

    $agent = User::create([
        'name' => 'Agent Partner',
        'email' => 'agent@partner.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'first_name' => 'Kavya',
        'last_name' => 'Nair',
        'mobile' => '9444444444',
        'email' => 'kavya@test.com',
        'source' => 'agent',
        'lead_score' => 0,
        'temperature' => 'warm',
        'status' => 'converted',
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1.0',
    ]);

    return [$institution, $admin, $agent, $lead];
}

// ─── Commission: create dispatches job ────────────────────────────────────

test('create dispatches ProcessAgentCommissionJob (AG-006)', function (): void {
    Queue::fake();

    [$institution, $admin, $agent, $lead] = makeCommissionContext();

    $service = app(AgentCommissionService::class);

    $commission = $service->create($institution->id, [
        'agent_user_id' => $agent->id,
        'lead_id' => $lead->id,
        'commission_type' => 'fixed',
        'commission_amount' => 5000.00,
    ]);

    expect($commission)->toBeInstanceOf(AgentCommission::class)
        ->and($commission->status)->toBe(CommissionStatus::Pending)
        ->and($commission->commission_amount)->toBe(5000.00);

    Queue::assertPushed(ProcessAgentCommissionJob::class);
});

// ─── Commission: approve changes status and fires event ──────────────────

test('approve sets commission to approved and fires AgentCommissionApprovedEvent (AG-006)', function (): void {
    Event::fake([AgentCommissionApprovedEvent::class]);

    [$institution, $admin, $agent, $lead] = makeCommissionContext();

    $service = app(AgentCommissionService::class);

    $commission = AgentCommission::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'agent_user_id' => $agent->id,
        'lead_id' => $lead->id,
        'commission_type' => 'fixed',
        'commission_amount' => 3000.00,
        'status' => CommissionStatus::Pending,
    ]);

    $service->approve($commission, $admin->id, 'Approved after lead conversion verified.');

    $commission->refresh();

    expect($commission->status)->toBe(CommissionStatus::Approved)
        ->and($commission->approved_by)->toBe($admin->id)
        ->and($commission->approved_at)->not->toBeNull();

    Event::assertDispatched(AgentCommissionApprovedEvent::class);
});

// ─── Commission: reject sets status to rejected ───────────────────────────

test('reject sets AgentCommission status to rejected (AG-006)', function (): void {
    [$institution, $admin, $agent, $lead] = makeCommissionContext();

    $service = app(AgentCommissionService::class);

    $commission = AgentCommission::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'agent_user_id' => $agent->id,
        'lead_id' => $lead->id,
        'commission_type' => 'fixed',
        'commission_amount' => 2000.00,
        'status' => CommissionStatus::Pending,
    ]);

    $service->reject($commission);

    $commission->refresh();

    expect($commission->status)->toBe(CommissionStatus::Rejected);
});

// ─── Commission: markPaid sets status and records payout reference ────────

test('markPaid sets AgentCommission to paid and records payout_reference (AG-006)', function (): void {
    [$institution, $admin, $agent, $lead] = makeCommissionContext();

    $service = app(AgentCommissionService::class);

    $commission = AgentCommission::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'agent_user_id' => $agent->id,
        'lead_id' => $lead->id,
        'commission_type' => 'fixed',
        'commission_amount' => 4500.00,
        'status' => CommissionStatus::Approved,
        'approved_by' => $admin->id,
        'approved_at' => now(),
    ]);

    $service->markPaid($commission, 'TXN-PAY-99999');

    $commission->refresh();

    expect($commission->status)->toBe(CommissionStatus::Paid)
        ->and($commission->payout_reference)->toBe('TXN-PAY-99999')
        ->and($commission->paid_at)->not->toBeNull();
});
