<?php

declare(strict_types=1);

use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Events\CRM\Communication\EmailSentEvent;
use App\Jobs\CRM\Automation\ExecuteWorkflowActionsJob;
use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Tag;
use App\Models\CRM\Task;
use App\Models\CRM\WorkflowActionExecution;
use App\Models\CRM\WorkflowInstance;
use App\Models\CRM\WorkflowStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function makeActionInstitution(string $suffix = 'x'): array
{
    $institution = Institution::create([
        'name' => 'Automation Action Test '.$suffix,
        'code' => 'AAT'.strtoupper($suffix),
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Action Owner '.$suffix,
        'email' => 'action-owner-'.$suffix.'@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    return [$institution, $owner];
}

function makeActionLead(int $institutionId): Lead
{
    return Lead::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'first_name' => 'Action',
        'last_name' => 'Lead',
        'mobile' => '9000000000',
        'email' => 'action@example.com',
        'source' => LeadSource::WEBSITE_ORGANIC->value,
        'lead_score' => 40,
        'temperature' => LeadTemperature::WARM->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_ip' => '127.0.0.1',
        'consent_form_version' => 'v1.0',
    ]);
}

function makeActionWorkflow(int $institutionId, int $ownerId): AutomationWorkflow
{
    return AutomationWorkflow::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'created_by' => $ownerId,
        'name' => 'Action Workflow',
        'status' => 'active',
        'trigger_type' => 'lead_created',
        'trigger_config' => [],
        'version' => 1,
        'published_at' => now(),
    ]);
}

it('executes update_lead_field action and stores execution record', function (): void {
    [$institution, $owner] = makeActionInstitution('a');
    $lead = makeActionLead($institution->id);
    $workflow = makeActionWorkflow($institution->id, $owner->id);

    $step = WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 1,
        'node_type' => 'action',
        'name' => 'Set city',
        'config' => [
            'action_type' => 'update_lead_field',
            'field' => 'city',
            'value' => 'Mumbai',
        ],
    ]);

    $instance = WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'lead_id' => $lead->id,
        'status' => 'pending',
        'current_workflow_step_id' => $step->id,
        'started_at' => now(),
        'context' => ['trigger_type' => 'lead_created'],
    ]);

    $job = new ExecuteWorkflowActionsJob($institution->id, $instance->id);
    $job->handle(app(\App\Services\CRM\Marketing\AutomationActionService::class));

    expect($lead->fresh()->city)->toBe('Mumbai');
    expect(WorkflowActionExecution::withoutGlobalScopes()->where('workflow_instance_id', $instance->id)->count())->toBe(1);
    expect(WorkflowActionExecution::withoutGlobalScopes()->where('workflow_instance_id', $instance->id)->first()?->status)->toBe('success');
});

it('executes webhook_call action and tracks success', function (): void {
    Http::fake([
        'https://example.test/hooks/automation' => Http::response(['ok' => true], 200),
    ]);

    [$institution, $owner] = makeActionInstitution('b');
    $lead = makeActionLead($institution->id);
    $workflow = makeActionWorkflow($institution->id, $owner->id);

    $step = WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 1,
        'node_type' => 'action',
        'name' => 'Webhook',
        'config' => [
            'action_type' => 'webhook_call',
            'url' => 'https://example.test/hooks/automation',
        ],
    ]);

    $instance = WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'lead_id' => $lead->id,
        'status' => 'pending',
        'current_workflow_step_id' => $step->id,
        'started_at' => now(),
        'context' => ['trigger_type' => 'lead_created'],
    ]);

    $job = new ExecuteWorkflowActionsJob($institution->id, $instance->id);
    $job->handle(app(\App\Services\CRM\Marketing\AutomationActionService::class));

    Http::assertSentCount(1);

    $execution = WorkflowActionExecution::withoutGlobalScopes()
        ->where('workflow_instance_id', $instance->id)
        ->first();

    expect($execution?->status)->toBe('success');
});

it('marks unsupported or incomplete actions as failed/pending without crashing', function (): void {
    [$institution, $owner] = makeActionInstitution('c');
    $lead = makeActionLead($institution->id);
    $workflow = makeActionWorkflow($institution->id, $owner->id);

    WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 1,
        'node_type' => 'action',
        'name' => 'Unsupported action',
        'config' => [
            'action_type' => 'invalid_action',
        ],
    ]);

    $instance = WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'lead_id' => $lead->id,
        'status' => 'pending',
        'started_at' => now(),
        'context' => ['trigger_type' => 'lead_created'],
    ]);

    ExecuteWorkflowActionsJob::dispatchSync($institution->id, $instance->id);

    $execution = WorkflowActionExecution::withoutGlobalScopes()
        ->where('workflow_instance_id', $instance->id)
        ->first();

    expect($execution)->not()->toBeNull();
    expect($execution?->status)->toBe('failed');
});

it('executes add_tag action and links lead tag record', function (): void {
    [$institution, $owner] = makeActionInstitution('d');
    $lead = makeActionLead($institution->id);
    $workflow = makeActionWorkflow($institution->id, $owner->id);

    $step = WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 1,
        'node_type' => 'action',
        'name' => 'Add tag',
        'config' => [
            'action_type' => 'add_tag',
            'tag_name' => 'hot_nurture',
            'color' => 'amber',
        ],
    ]);

    $instance = WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'lead_id' => $lead->id,
        'status' => 'pending',
        'current_workflow_step_id' => $step->id,
        'started_at' => now(),
        'context' => ['trigger_type' => 'lead_created'],
    ]);

    ExecuteWorkflowActionsJob::dispatchSync($institution->id, $instance->id);

    $tag = Tag::withoutGlobalScopes()->where('institution_id', $institution->id)->where('name', 'hot_nurture')->first();
    expect($tag)->not()->toBeNull();
    expect($lead->fresh()->tags()->where('crm_tag_id', $tag?->id)->exists())->toBeTrue();
});

it('executes create_task action and creates crm task', function (): void {
    [$institution, $owner] = makeActionInstitution('e');
    $lead = makeActionLead($institution->id);
    $workflow = makeActionWorkflow($institution->id, $owner->id);

    $step = WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 1,
        'node_type' => 'action',
        'name' => 'Create follow-up task',
        'config' => [
            'action_type' => 'create_task',
            'title' => 'Call lead for counselling follow-up',
            'description' => 'Contact lead within 24 hours.',
            'priority' => 'high',
        ],
    ]);

    $instance = WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'lead_id' => $lead->id,
        'status' => 'pending',
        'current_workflow_step_id' => $step->id,
        'started_at' => now(),
        'context' => ['trigger_type' => 'lead_created'],
    ]);

    ExecuteWorkflowActionsJob::dispatchSync($institution->id, $instance->id);

    expect(Task::withoutGlobalScopes()->where('institution_id', $institution->id)->where('lead_id', $lead->id)->count())->toBe(1);
});

it('executes assign_counsellor action', function (): void {
    [$institution, $owner] = makeActionInstitution('f');
    $lead = makeActionLead($institution->id);
    $workflow = makeActionWorkflow($institution->id, $owner->id);

    $counsellor = User::create([
        'name' => 'Assigned Counsellor',
        'email' => 'assigned-counsellor@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $step = WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 1,
        'node_type' => 'action',
        'name' => 'Assign counsellor',
        'config' => [
            'action_type' => 'assign_counsellor',
            'counsellor_id' => $counsellor->id,
        ],
    ]);

    $instance = WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'lead_id' => $lead->id,
        'status' => 'pending',
        'current_workflow_step_id' => $step->id,
        'started_at' => now(),
        'context' => ['trigger_type' => 'lead_created'],
    ]);

    ExecuteWorkflowActionsJob::dispatchSync($institution->id, $instance->id);

    expect((int) $lead->fresh()->assigned_counsellor_id)->toBe((int) $counsellor->id);
});

it('executes enrol_in_workflow action and creates target workflow instance', function (): void {
    [$institution, $owner] = makeActionInstitution('g');
    $lead = makeActionLead($institution->id);

    $sourceWorkflow = makeActionWorkflow($institution->id, $owner->id);
    $targetWorkflow = makeActionWorkflow($institution->id, $owner->id);
    $targetWorkflow->update(['name' => 'Target Workflow']);

    WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $targetWorkflow->id,
        'step_order' => 0,
        'node_type' => 'trigger',
        'name' => 'Target start',
        'config' => ['event' => 'manual'],
    ]);

    $step = WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $sourceWorkflow->id,
        'step_order' => 1,
        'node_type' => 'action',
        'name' => 'Enroll in target workflow',
        'config' => [
            'action_type' => 'enrol_in_workflow',
            'target_workflow_id' => $targetWorkflow->id,
        ],
    ]);

    $instance = WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $sourceWorkflow->id,
        'lead_id' => $lead->id,
        'status' => 'pending',
        'current_workflow_step_id' => $step->id,
        'started_at' => now(),
        'context' => ['trigger_type' => 'lead_created'],
    ]);

    ExecuteWorkflowActionsJob::dispatchSync($institution->id, $instance->id);

    expect(
        WorkflowInstance::withoutGlobalScopes()
            ->where('institution_id', $institution->id)
            ->where('automation_workflow_id', $targetWorkflow->id)
            ->where('lead_id', $lead->id)
            ->count()
    )->toBe(1);
});

it('schedules next drip step using delay_minutes instead of executing immediately', function (): void {
    Queue::fake();

    [$institution, $owner] = makeActionInstitution('h');
    $lead = makeActionLead($institution->id);
    $workflow = makeActionWorkflow($institution->id, $owner->id);

    $firstStep = WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 1,
        'node_type' => 'action',
        'name' => 'Set city',
        'config' => [
            'action_type' => 'update_lead_field',
            'field' => 'city',
            'value' => 'Delhi',
        ],
    ]);

    $secondStep = WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 2,
        'node_type' => 'action',
        'name' => 'Set state',
        'delay_minutes' => 60,
        'config' => [
            'action_type' => 'update_lead_field',
            'field' => 'state',
            'value' => 'Delhi NCR',
        ],
    ]);

    $instance = WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'lead_id' => $lead->id,
        'status' => 'pending',
        'current_workflow_step_id' => $firstStep->id,
        'started_at' => now(),
        'context' => ['trigger_type' => 'lead_created'],
    ]);

    $job = new ExecuteWorkflowActionsJob($institution->id, $instance->id);
    $job->handle(app(\App\Services\CRM\Marketing\AutomationActionService::class));

    expect($lead->fresh()->city)->toBe('Delhi');
    expect($lead->fresh()->state)->toBeNull();

    $executedStepIds = WorkflowActionExecution::withoutGlobalScopes()
        ->where('workflow_instance_id', $instance->id)
        ->pluck('workflow_step_id')
        ->all();

    expect($executedStepIds)->toContain($firstStep->id);
    expect($executedStepIds)->not->toContain($secondStep->id);

    $freshInstance = $instance->fresh();

    expect((int) $freshInstance->current_workflow_step_id)->toBe((int) $secondStep->id);
    expect($freshInstance->status)->toBe('running');
    expect($freshInstance->context['next_action_step_id'] ?? null)->toBe($secondStep->id);
    expect($freshInstance->context['next_action_due_at'] ?? null)->not->toBeNull();

    Queue::assertPushed(ExecuteWorkflowActionsJob::class, function (ExecuteWorkflowActionsJob $job) use ($instance): bool {
        return $job->workflowInstanceId === $instance->id
            && $job->institutionId > 0
            && $job->delay !== null;
    });
});

it('executes send_email action with MA-004 A/B subject-content variant selection', function (): void {
    Mail::fake();
    Event::fake([EmailSentEvent::class]);

    [$institution, $owner] = makeActionInstitution('i');
    $lead = makeActionLead($institution->id);
    $workflow = makeActionWorkflow($institution->id, $owner->id);

    $step = WorkflowStep::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'step_order' => 1,
        'node_type' => 'action',
        'name' => 'Send A/B email',
        'config' => [
            'action_type' => 'send_email',
            'from_name' => 'Admissions Team',
            'from_email' => 'admissions@example.com',
            'ab_test' => [
                'enabled' => true,
                'strategy' => 'split',
                'salt' => 'ma004-test',
                'variants' => [
                    [
                        'id' => 'A',
                        'name' => 'Variant A',
                        'subject' => 'A/B Subject A',
                        'custom_body_html' => '<p>A/B Body A</p>',
                    ],
                    [
                        'id' => 'B',
                        'name' => 'Variant B',
                        'subject' => 'A/B Subject B',
                        'custom_body_html' => '<p>A/B Body B</p>',
                    ],
                ],
            ],
        ],
    ]);

    $instance = WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'automation_workflow_id' => $workflow->id,
        'lead_id' => $lead->id,
        'status' => 'pending',
        'current_workflow_step_id' => $step->id,
        'started_at' => now(),
        'context' => ['trigger_type' => 'lead_created'],
    ]);

    ExecuteWorkflowActionsJob::dispatchSync($institution->id, $instance->id);

    $execution = WorkflowActionExecution::withoutGlobalScopes()
        ->where('workflow_instance_id', $instance->id)
        ->where('workflow_step_id', $step->id)
        ->first();

    expect($execution)->not()->toBeNull();
    expect($execution?->status)->toBe('success');
    expect($execution?->result['meta']['ab_test']['selected_variant_id'] ?? null)->not->toBeNull();

    $selectedSubject = $execution?->result['meta']['ab_test']['selected_subject'] ?? null;
    expect($selectedSubject)->toBeIn(['A/B Subject A', 'A/B Subject B']);

    $log = \App\Models\CRM\CommunicationLog::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->where('lead_id', $lead->id)
        ->latest('id')
        ->first();

    expect($log)->not()->toBeNull();
    expect($log?->subject)->toBe($selectedSubject);
});
