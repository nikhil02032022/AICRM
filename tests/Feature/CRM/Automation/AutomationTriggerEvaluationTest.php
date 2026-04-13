<?php

declare(strict_types=1);

use App\Enums\CRM\ActivityType;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Jobs\CRM\Automation\EvaluateAutomationTriggerJob;
use App\Jobs\CRM\Automation\EvaluateEventBasedAutomationTriggersJob;
use App\Jobs\CRM\Automation\EvaluateInactivityAutomationTriggersJob;
use App\Jobs\CRM\Automation\EvaluateTimedAutomationTriggersJob;
use App\Models\CRM\Activity;
use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\WorkflowInstance;
use App\Models\CRM\WorkflowStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeInstitutionAndOwner(string $suffix = 'x'): array
{
    $institution = Institution::create([
        'name' => 'Automation Trigger Test '.$suffix,
        'code' => 'ATT'.strtoupper($suffix),
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Owner '.$suffix,
        'email' => 'owner-'.$suffix.'@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    return [$institution, $owner];
}

function makeLead(int $institutionId, LeadSource $source = LeadSource::WEBSITE_ORGANIC): Lead
{
    return Lead::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'first_name' => 'Trigger',
        'last_name' => 'Lead',
        'mobile' => '9000000000',
        'email' => 'trigger@example.com',
        'source' => $source->value,
        'lead_score' => 55,
        'temperature' => LeadTemperature::WARM->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_ip' => '127.0.0.1',
        'consent_form_version' => 'v1.0',
    ]);
}

function makeWorkflow(int $institutionId, int $ownerId, string $triggerType, array $triggerConfig = []): AutomationWorkflow
{
    $workflow = AutomationWorkflow::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'created_by' => $ownerId,
        'name' => 'Workflow '.$triggerType,
        'status' => 'active',
        'trigger_type' => $triggerType,
        'trigger_config' => $triggerConfig,
        'version' => 1,
        'published_at' => now(),
    ]);

    WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 0,
        'node_type' => 'trigger',
        'name' => 'Start',
        'config' => ['event' => $triggerType],
    ]);

    WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 1,
        'node_type' => 'action',
        'name' => 'Action',
        'config' => ['type' => 'send_email'],
    ]);

    return $workflow;
}

it('enrolls lead instance for lead_created trigger', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('a');
    $lead = makeLead($institution->id);

    makeWorkflow($institution->id, $owner->id, 'lead_created', ['source' => LeadSource::WEBSITE_ORGANIC->value]);

    EvaluateAutomationTriggerJob::dispatchSync(
        institutionId: $institution->id,
        leadId: $lead->id,
        triggerType: 'lead_created',
        context: ['source' => LeadSource::WEBSITE_ORGANIC->value],
    );

    expect(WorkflowInstance::withoutGlobalScopes()->count())->toBe(1);
});

it('matches programme-specific workflows by programme_ids and programme_codes', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('ma008');
    $lead = makeLead($institution->id);

    $programmeA = CrmProgramme::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'name' => 'MBA',
        'code' => 'MBA',
        'level' => 'PG',
        'department' => 'Management',
        'is_active' => true,
    ]);

    $programmeB = CrmProgramme::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'name' => 'B.Tech',
        'code' => 'BTECH',
        'level' => 'UG',
        'department' => 'Engineering',
        'is_active' => true,
    ]);

    $lead->programmeInterests()->sync([$programmeA->id]);

    makeWorkflow($institution->id, $owner->id, 'lead_created', [
        'programme_ids' => [$programmeB->id],
    ]);

    EvaluateAutomationTriggerJob::dispatchSync(
        institutionId: $institution->id,
        leadId: $lead->id,
        triggerType: 'lead_created',
        context: [],
    );

    expect(WorkflowInstance::withoutGlobalScopes()->count())->toBe(0);

    AutomationWorkflow::withoutGlobalScopes()->delete();

    makeWorkflow($institution->id, $owner->id, 'lead_created', [
        'programme_ids' => [$programmeA->id],
        'programme_codes' => ['MBA'],
    ]);

    EvaluateAutomationTriggerJob::dispatchSync(
        institutionId: $institution->id,
        leadId: $lead->id,
        triggerType: 'lead_created',
        context: [],
    );

    expect(WorkflowInstance::withoutGlobalScopes()->count())->toBe(1);
});

it('matches status_changed trigger based on new_status filter', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('b');
    $lead = makeLead($institution->id);

    makeWorkflow($institution->id, $owner->id, 'status_changed', ['new_status' => LeadStatus::CONTACTED->value]);

    EvaluateAutomationTriggerJob::dispatchSync(
        institutionId: $institution->id,
        leadId: $lead->id,
        triggerType: 'status_changed',
        context: ['new_status' => LeadStatus::NEW_ENQUIRY->value],
    );

    expect(WorkflowInstance::withoutGlobalScopes()->count())->toBe(0);

    EvaluateAutomationTriggerJob::dispatchSync(
        institutionId: $institution->id,
        leadId: $lead->id,
        triggerType: 'status_changed',
        context: ['new_status' => LeadStatus::CONTACTED->value],
    );

    expect(WorkflowInstance::withoutGlobalScopes()->count())->toBe(1);
});

it('matches lead_score_changed trigger based on new_temperature filter', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('e');
    $lead = makeLead($institution->id);

    makeWorkflow($institution->id, $owner->id, 'lead_score_changed', ['new_temperature' => LeadTemperature::HOT->value]);

    EvaluateAutomationTriggerJob::dispatchSync(
        institutionId: $institution->id,
        leadId: $lead->id,
        triggerType: 'lead_score_changed',
        context: ['new_temperature' => LeadTemperature::WARM->value],
    );

    expect(WorkflowInstance::withoutGlobalScopes()->count())->toBe(0);

    EvaluateAutomationTriggerJob::dispatchSync(
        institutionId: $institution->id,
        leadId: $lead->id,
        triggerType: 'lead_score_changed',
        context: ['new_temperature' => LeadTemperature::HOT->value],
    );

    expect(WorkflowInstance::withoutGlobalScopes()->count())->toBe(1);
});

it('enrolls lead for link_clicked trigger', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('f');
    $lead = makeLead($institution->id);

    makeWorkflow($institution->id, $owner->id, 'link_clicked');

    EvaluateAutomationTriggerJob::dispatchSync(
        institutionId: $institution->id,
        leadId: $lead->id,
        triggerType: 'link_clicked',
        context: ['communication_log_uuid' => '00000000-0000-0000-0000-000000000001'],
    );

    expect(WorkflowInstance::withoutGlobalScopes()->count())->toBe(1);
});

it('enrolls lead for re_engagement trigger when reason is cold', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('g');
    $lead = makeLead($institution->id);

    makeWorkflow($institution->id, $owner->id, 're_engagement', ['reason' => 'cold']);

    EvaluateAutomationTriggerJob::dispatchSync(
        institutionId: $institution->id,
        leadId: $lead->id,
        triggerType: 're_engagement',
        context: ['reason' => 'cold', 'new_temperature' => LeadTemperature::COLD->value],
    );

    expect(WorkflowInstance::withoutGlobalScopes()->count())->toBe(1);
});

it('enrolls leads for due date_time_based workflows', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('c');
    makeLead($institution->id);

    makeWorkflow($institution->id, $owner->id, 'date_time_based', [
        'run_at' => now()->subMinutes(5)->toIso8601String(),
    ]);

    EvaluateTimedAutomationTriggersJob::dispatchSync();

    expect(WorkflowInstance::withoutGlobalScopes()->where('institution_id', $institution->id)->count())->toBe(1);
});

it('enrolls leads for due event_based workflows', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('ma009-a');
    makeLead($institution->id);

    makeWorkflow($institution->id, $owner->id, 'event_based', [
        'event_type' => 'open_day',
        'event_at' => now()->subMinutes(15)->toIso8601String(),
        'window_minutes' => 60,
    ]);

    EvaluateEventBasedAutomationTriggersJob::dispatchSync();

    $instance = WorkflowInstance::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->latest('id')
        ->first();

    expect($instance)->not()->toBeNull();
    expect((string) data_get($instance?->context, 'trigger_type'))->toBe('event_based:open_day:event');
});

it('enrolls reminder only once per day for event_based workflows', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('ma009-b');
    makeLead($institution->id);

    makeWorkflow($institution->id, $owner->id, 'event_based', [
        'event_type' => 'webinar',
        'event_at' => now()->addDays(2)->toIso8601String(),
        'reminder_offsets_days' => [2, 1],
        'window_minutes' => 60,
    ]);

    EvaluateEventBasedAutomationTriggersJob::dispatchSync();
    EvaluateEventBasedAutomationTriggersJob::dispatchSync();

    $instances = WorkflowInstance::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->where('context->trigger_type', 'event_based:webinar:reminder_2d')
        ->get();

    expect($instances)->toHaveCount(1);
});

it('enrolls only inactive leads for inactivity_timeout workflows', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('d');

    $inactiveLead = makeLead($institution->id);
    $activeLead = makeLead($institution->id, LeadSource::WALK_IN);

    makeWorkflow($institution->id, $owner->id, 'inactivity_timeout', [
        'inactivity_days' => 14,
    ]);

    Activity::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'subject_type' => Lead::class,
        'subject_id' => $activeLead->id,
        'type' => ActivityType::NOTE->value,
        'body' => 'Recent activity note',
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    EvaluateInactivityAutomationTriggersJob::dispatchSync();

    $instances = WorkflowInstance::withoutGlobalScopes()->where('institution_id', $institution->id)->get();

    expect($instances)->toHaveCount(1);
    expect((int) $instances->first()->lead_id)->toBe((int) $inactiveLead->id);
});

it('enrolls inactive leads for re_engagement workflows configured with inactivity reason', function (): void {
    [$institution, $owner] = makeInstitutionAndOwner('h');

    $inactiveLead = makeLead($institution->id);
    $activeLead = makeLead($institution->id, LeadSource::WALK_IN);

    makeWorkflow($institution->id, $owner->id, 're_engagement', [
        'reason' => 'inactive',
        'inactivity_days' => 14,
    ]);

    Activity::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'subject_type' => Lead::class,
        'subject_id' => $activeLead->id,
        'type' => ActivityType::NOTE->value,
        'body' => 'Recent activity note',
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    EvaluateInactivityAutomationTriggersJob::dispatchSync();

    $instances = WorkflowInstance::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->where('context->trigger_type', 're_engagement:inactive')
        ->get();

    expect($instances)->toHaveCount(1);
    expect((int) $instances->first()->lead_id)->toBe((int) $inactiveLead->id);
});
