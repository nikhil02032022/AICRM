<?php

declare(strict_types=1);

use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Events\CRM\LeadStatusChangedEvent;
use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\WorkflowInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function makeNurtureInstitution(string $suffix = 'n'): array
{
    $institution = Institution::create([
        'name' => 'Nurture Exit Test '.$suffix,
        'code' => 'NET'.strtoupper($suffix),
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Nurture Owner '.$suffix,
        'email' => 'nurture-owner-'.$suffix.'@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'first_name' => 'Nurture',
        'last_name' => 'Lead',
        'mobile' => '9111111111',
        'email' => 'nurture@example.com',
        'source' => LeadSource::WEBSITE_ORGANIC->value,
        'lead_score' => 30,
        'temperature' => LeadTemperature::COLD->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_ip' => '127.0.0.1',
        'consent_form_version' => 'v1.0',
    ]);

    return [$institution, $owner, $lead];
}

function createWorkflowInstanceForLead(
    int $institutionId,
    int $ownerId,
    int $leadId,
    string $name,
    array $triggerConfig,
    string $status = 'pending',
): WorkflowInstance {
    $workflow = AutomationWorkflow::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'created_by' => $ownerId,
        'name' => $name,
        'status' => 'active',
        'trigger_type' => 'lead_created',
        'trigger_config' => $triggerConfig,
        'version' => 1,
        'published_at' => now(),
    ]);

    return WorkflowInstance::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'automation_workflow_id' => $workflow->id,
        'lead_id' => $leadId,
        'status' => $status,
        'started_at' => now(),
        'context' => ['trigger_type' => 'lead_created'],
    ]);
}

it('exits active nurture workflow instances when status becomes contacted', function (): void {
    Queue::fake();

    [$institution, $owner, $lead] = makeNurtureInstitution('a');

    $nurtureInstance = createWorkflowInstanceForLead(
        institutionId: $institution->id,
        ownerId: $owner->id,
        leadId: $lead->id,
        name: 'Cold Lead Nurture Drip',
        triggerConfig: ['journey_type' => 'nurture'],
        status: 'running',
    );

    $nonNurtureInstance = createWorkflowInstanceForLead(
        institutionId: $institution->id,
        ownerId: $owner->id,
        leadId: $lead->id,
        name: 'Application Reminder Sequence',
        triggerConfig: ['journey_type' => 'event'],
        status: 'pending',
    );

    event(new LeadStatusChangedEvent(
        lead: $lead,
        previousStatus: LeadStatus::NEW_ENQUIRY,
        newStatus: LeadStatus::CONTACTED,
    ));

    $nurtureInstance->refresh();
    $nonNurtureInstance->refresh();

    expect($nurtureInstance->status)->toBe('exited');
    expect($nurtureInstance->completed_at)->not->toBeNull();
    expect($nurtureInstance->context['exited_on_status'] ?? null)->toBe(LeadStatus::CONTACTED->value);
    expect($nurtureInstance->context['exit_reason'] ?? null)->toBe('lead_status_progressed');

    expect($nonNurtureInstance->status)->toBe('pending');
});

it('does not exit nurture workflow while lead remains in new_enquiry', function (): void {
    Queue::fake();

    [$institution, $owner, $lead] = makeNurtureInstitution('b');

    $nurtureInstance = createWorkflowInstanceForLead(
        institutionId: $institution->id,
        ownerId: $owner->id,
        leadId: $lead->id,
        name: 'Nurture Welcome Drip',
        triggerConfig: ['is_nurture' => true],
        status: 'pending',
    );

    event(new LeadStatusChangedEvent(
        lead: $lead,
        previousStatus: LeadStatus::NEW_ENQUIRY,
        newStatus: LeadStatus::NEW_ENQUIRY,
    ));

    $nurtureInstance->refresh();

    expect($nurtureInstance->status)->toBe('pending');
    expect($nurtureInstance->completed_at)->toBeNull();
});
