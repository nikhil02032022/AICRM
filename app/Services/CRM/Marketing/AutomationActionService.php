<?php

declare(strict_types=1);

namespace App\Services\CRM\Marketing;

use App\DTOs\CRM\SendEmailDTO;
use App\Enums\CRM\ActivityType;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\TaskStatus;
use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\Activity;
use App\Models\CRM\CommunicationTemplate;
use App\Models\CRM\Lead;
use App\Models\CRM\Tag;
use App\Models\CRM\Task;
use App\Models\CRM\WorkflowActionExecution;
use App\Models\CRM\WorkflowInstance;
use App\Models\CRM\WorkflowStep;
use Carbon\CarbonImmutable;
use App\Services\CRM\Communication\EmailService;
use App\Services\CRM\Communication\SmsService;
use App\Services\CRM\Communication\WhatsAppService;
use Illuminate\Support\Facades\Http;

// BRD: CRM-MA-003 — Action execution runtime for workflow action node types
final class AutomationActionService
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly SmsService $smsService,
        private readonly WhatsAppService $whatsAppService,
    ) {}

    /**
     * BRD: CRM-MA-005 - Execute one due action step and compute the next schedule.
     *
     * @return array{completed: bool, next_run_at: CarbonImmutable|null}
     */
    public function executeDueActionForInstance(WorkflowInstance $instance): array
    {
        $instance->loadMissing(['workflow.steps', 'lead', 'currentStep']);

        $workflow = $instance->workflow;
        $lead = $instance->lead;

        if ($workflow === null || $lead === null) {
            return [
                'completed' => true,
                'next_run_at' => null,
            ];
        }

        $actionSteps = $workflow->steps
            ->filter(static fn (WorkflowStep $step): bool => $step->node_type?->value === 'action')
            ->values();

        if ($actionSteps->isEmpty()) {
            $instance->update([
                'status' => 'completed',
                'current_workflow_step_id' => null,
                'completed_at' => now(),
            ]);

            return [
                'completed' => true,
                'next_run_at' => null,
            ];
        }

        $currentStep = $instance->currentStep;
        if ($currentStep === null || $currentStep->node_type?->value !== 'action') {
            $currentStep = $actionSteps->first();
        }

        if ($currentStep === null) {
            $instance->update([
                'status' => 'completed',
                'current_workflow_step_id' => null,
                'completed_at' => now(),
            ]);

            return [
                'completed' => true,
                'next_run_at' => null,
            ];
        }

        $context = is_array($instance->context) ? $instance->context : [];
        $scheduledStepId = isset($context['next_action_step_id']) ? (int) $context['next_action_step_id'] : null;
        $scheduledAtRaw = isset($context['next_action_due_at']) && is_string($context['next_action_due_at'])
            ? $context['next_action_due_at']
            : null;

        // If this step already ran (retry or duplicate job), advance cursor to the next step.
        $alreadyExecuted = WorkflowActionExecution::withoutGlobalScopes()
            ->where('workflow_instance_id', $instance->id)
            ->where('workflow_step_id', $currentStep->id)
            ->exists();

        if ($alreadyExecuted) {
            $nextStep = $this->nextActionStep($actionSteps->all(), $currentStep->id);

            if ($nextStep === null) {
                $instance->update([
                    'status' => 'completed',
                    'current_workflow_step_id' => null,
                    'completed_at' => now(),
                    'context' => $this->withoutSchedulingContext($context),
                ]);

                return [
                    'completed' => true,
                    'next_run_at' => null,
                ];
            }

            $currentStep = $nextStep;
            $scheduledStepId = null;
            $scheduledAtRaw = null;
        }

        $delayMinutes = max(0, (int) ($currentStep->delay_minutes ?? 0));
        if ($scheduledStepId !== $currentStep->id) {
            $scheduledAt = $delayMinutes > 0
                ? CarbonImmutable::now()->addMinutes($delayMinutes)
                : null;

            $instance->update([
                'status' => 'running',
                'current_workflow_step_id' => $currentStep->id,
                'completed_at' => null,
                'context' => $this->withSchedulingContext($context, $currentStep->id, $scheduledAt),
            ]);

            if ($scheduledAt !== null) {
                return [
                    'completed' => false,
                    'next_run_at' => $scheduledAt,
                ];
            }

            $scheduledAtRaw = null;
        }

        if ($scheduledAtRaw !== null) {
            $scheduledAt = CarbonImmutable::parse($scheduledAtRaw);

            if ($scheduledAt->isFuture()) {
                return [
                    'completed' => false,
                    'next_run_at' => $scheduledAt,
                ];
            }
        }

        $actionType = (string) ($currentStep->config['action_type'] ?? '');
        if ($actionType === '') {
            $result = [
                'status' => 'failed',
                'message' => 'action_type is required for action step execution',
            ];
        } else {
            try {
                $result = $this->executeAction($lead, $workflow, $actionType, $currentStep->config ?? []);
            } catch (\Throwable $exception) {
                $result = [
                    'status' => 'failed',
                    'message' => 'Action execution failed: '.$exception->getMessage(),
                ];
            }
        }

        WorkflowActionExecution::create([
            'institution_id' => $instance->institution_id,
            'campus_id' => $instance->campus_id,
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $currentStep->id,
            'action_type' => $actionType !== '' ? $actionType : null,
            'status' => $result['status'],
            'payload' => ['config' => $currentStep->config],
            'result' => array_filter([
                'message' => $result['message'],
                'meta' => $result['meta'] ?? null,
            ], static fn (mixed $value): bool => $value !== null),
            'executed_at' => now(),
        ]);

        $nextStep = $this->nextActionStep($actionSteps->all(), $currentStep->id);

        if ($nextStep === null) {
            $instance->update([
                'status' => 'completed',
                'current_workflow_step_id' => null,
                'completed_at' => now(),
                'context' => $this->withoutSchedulingContext($context),
            ]);

            return [
                'completed' => true,
                'next_run_at' => null,
            ];
        }

        $nextDelayMinutes = max(0, (int) ($nextStep->delay_minutes ?? 0));
        $nextRunAt = $nextDelayMinutes > 0
            ? CarbonImmutable::now()->addMinutes($nextDelayMinutes)
            : null;

        $instance->update([
            'status' => 'running',
            'current_workflow_step_id' => $nextStep->id,
            'completed_at' => null,
            'context' => $this->withSchedulingContext($context, $nextStep->id, $nextRunAt),
        ]);

        return [
            'completed' => false,
            'next_run_at' => $nextRunAt,
        ];
    }

    /**
     * @param list<WorkflowStep> $actionSteps
     */
    private function nextActionStep(array $actionSteps, int $currentStepId): ?WorkflowStep
    {
        $foundCurrent = false;

        foreach ($actionSteps as $step) {
            if ($foundCurrent) {
                return $step;
            }

            if ((int) $step->id === $currentStepId) {
                $foundCurrent = true;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function withSchedulingContext(array $context, int $stepId, ?CarbonImmutable $dueAt): array
    {
        $context['next_action_step_id'] = $stepId;
        $context['next_action_due_at'] = $dueAt?->toIso8601String();

        return $context;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function withoutSchedulingContext(array $context): array
    {
        unset($context['next_action_step_id'], $context['next_action_due_at']);

        return $context;
    }

    /**
     * @param array<string, mixed> $config
     * @return array{status:string,message:string,meta?:array<string,mixed>}
     */
    private function executeAction(Lead $lead, AutomationWorkflow $workflow, string $actionType, array $config): array
    {
        return match ($actionType) {
            'send_email' => $this->sendEmail($lead, $config),
            'send_sms' => $this->sendSms($lead, $config),
            'send_whatsapp' => $this->sendWhatsApp($lead, $config),
            'assign_counsellor' => $this->assignCounsellor($lead, $config),
            'update_lead_field' => $this->updateLeadField($lead, $config),
            'enrol_in_workflow' => $this->enrolInWorkflow($lead, $config),
            'webhook_call' => $this->callWebhook($lead, $workflow, $config),
            'add_tag' => $this->addTag($lead, $config),
            'create_task' => $this->createTask($lead, $config),
            default => ['status' => 'failed', 'message' => 'Unsupported action type: '.$actionType],
        };
    }

    /** @param array<string, mixed> $config
     *  @return array{status:string,message:string,meta?:array<string,mixed>}
     */
    private function sendEmail(Lead $lead, array $config): array
    {
        $resolvedConfig = $config;
        $variantMeta = $this->resolveAbTestVariant($lead, $config);

        if ($variantMeta !== null && isset($variantMeta['resolved_config']) && is_array($variantMeta['resolved_config'])) {
            $resolvedConfig = $variantMeta['resolved_config'];
        }

        $templateId = (int) ($resolvedConfig['template_id'] ?? 0);
        $customBodyHtml = isset($resolvedConfig['custom_body_html']) ? trim((string) $resolvedConfig['custom_body_html']) : '';

        if ($templateId <= 0 && $customBodyHtml === '') {
            return [
                'status' => 'failed',
                'message' => 'template_id or custom_body_html is required for send_email',
            ];
        }

        $dto = new SendEmailDTO(
            templateId: $templateId,
            fromName: (string) ($resolvedConfig['from_name'] ?? 'Admissions Team'),
            fromEmail: (string) ($resolvedConfig['from_email'] ?? 'noreply@example.com'),
            subject: isset($resolvedConfig['subject']) ? (string) $resolvedConfig['subject'] : null,
            customBodyHtml: $customBodyHtml !== '' ? $customBodyHtml : null,
        );

        $this->emailService->sendToLead($lead, $dto);

        $meta = [];

        if ($variantMeta !== null) {
            $meta['ab_test'] = $variantMeta;
            unset($meta['ab_test']['resolved_config']);
        }

        return [
            'status' => 'success',
            'message' => 'Email action executed',
            'meta' => $meta,
        ];
    }

    /** @param array<string, mixed> $config
     *  @return array{status:string,message:string,meta?:array<string,mixed>}
     */
    private function sendSms(Lead $lead, array $config): array
    {
        $templateId = (int) ($config['template_id'] ?? 0);
        $message = (string) ($config['message'] ?? '');

        if ($templateId <= 0 || $message === '') {
            return ['status' => 'failed', 'message' => 'template_id and message are required for send_sms'];
        }

        $template = CommunicationTemplate::query()->find($templateId);

        if ($template === null || $template->dlt_template_id === null) {
            return ['status' => 'failed', 'message' => 'Valid DLT template is required for send_sms'];
        }

        $dlt = \App\Models\CRM\DltTemplate::query()->find($template->dlt_template_id);

        if ($dlt === null) {
            return ['status' => 'failed', 'message' => 'DLT template record not found'];
        }

        $this->smsService->sendToLead($lead, $message, $dlt);

        return ['status' => 'success', 'message' => 'SMS action executed'];
    }

    /** @param array<string, mixed> $config
     *  @return array{status:string,message:string,meta?:array<string,mixed>}
     */
    private function sendWhatsApp(Lead $lead, array $config): array
    {
        $templateName = (string) ($config['template_name'] ?? '');
        if ($templateName === '') {
            return ['status' => 'failed', 'message' => 'template_name is required for send_whatsapp'];
        }

        $params = isset($config['params']) && is_array($config['params']) ? $config['params'] : [];
        $this->whatsAppService->sendTemplate($lead, $templateName, $params);

        return ['status' => 'success', 'message' => 'WhatsApp action executed'];
    }

    /** @param array<string, mixed> $config
     *  @return array{status:string,message:string,meta?:array<string,mixed>}
     */
    private function assignCounsellor(Lead $lead, array $config): array
    {
        $counsellorId = (int) ($config['counsellor_id'] ?? 0);

        if ($counsellorId <= 0) {
            return ['status' => 'failed', 'message' => 'counsellor_id is required for assign_counsellor'];
        }

        $lead->update(['assigned_counsellor_id' => $counsellorId]);

        return ['status' => 'success', 'message' => 'Counsellor assigned'];
    }

    /** @param array<string, mixed> $config
     *  @return array{status:string,message:string,meta?:array<string,mixed>}
     */
    private function updateLeadField(Lead $lead, array $config): array
    {
        $field = (string) ($config['field'] ?? '');
        $value = $config['value'] ?? null;

        $allowedFields = ['status', 'notes', 'city', 'state', 'assigned_counsellor_id'];

        if ($field === '' || ! in_array($field, $allowedFields, true)) {
            return ['status' => 'failed', 'message' => 'Invalid field for update_lead_field'];
        }

        if ($field === 'status' && is_string($value)) {
            $status = LeadStatus::tryFrom($value);
            if ($status === null) {
                return ['status' => 'failed', 'message' => 'Invalid lead status value'];
            }
        }

        $lead->update([$field => $value]);

        return ['status' => 'success', 'message' => 'Lead field updated'];
    }

    /** @param array<string, mixed> $config
     *  @return array{status:string,message:string,meta?:array<string,mixed>}
     */
    private function enrolInWorkflow(Lead $lead, array $config): array
    {
        $targetWorkflowId = (int) ($config['target_workflow_id'] ?? 0);

        if ($targetWorkflowId <= 0) {
            return ['status' => 'failed', 'message' => 'target_workflow_id is required for enrol_in_workflow'];
        }

        $targetWorkflow = AutomationWorkflow::withoutGlobalScopes()
            ->where('institution_id', $lead->institution_id)
            ->whereKey($targetWorkflowId)
            ->where('status', 'active')
            ->first();

        if ($targetWorkflow === null) {
            return ['status' => 'failed', 'message' => 'Target workflow not found or not active'];
        }

        $firstStep = $targetWorkflow->steps()->orderBy('step_order')->first();

        WorkflowInstance::create([
            'institution_id' => $lead->institution_id,
            'campus_id' => $lead->campus_id,
            'automation_workflow_id' => $targetWorkflow->id,
            'lead_id' => $lead->id,
            'status' => 'pending',
            'current_workflow_step_id' => $firstStep?->id,
            'started_at' => now(),
            'context' => ['trigger_type' => 'enrol_in_workflow_action'],
        ]);

        return ['status' => 'success', 'message' => 'Lead enrolled in target workflow'];
    }

    /** @param array<string, mixed> $config
     *  @return array{status:string,message:string,meta?:array<string,mixed>}
     */
    private function callWebhook(Lead $lead, AutomationWorkflow $workflow, array $config): array
    {
        $url = (string) ($config['url'] ?? '');

        if ($url === '') {
            return ['status' => 'failed', 'message' => 'url is required for webhook_call'];
        }

        $payload = [
            'workflow_uuid' => $workflow->uuid,
            'lead_uuid' => $lead->uuid,
            'trigger_type' => $workflow->trigger_type,
        ];

        $response = Http::timeout(10)->retry(2, 200)->post($url, $payload);

        return $response->successful()
            ? ['status' => 'success', 'message' => 'Webhook delivered']
            : ['status' => 'failed', 'message' => 'Webhook request failed with status '.$response->status()];
    }

    /** @param array<string, mixed> $config
     *  @return array{status:string,message:string,meta?:array<string,mixed>}
     */
    private function addTag(Lead $lead, array $config): array
    {
        $tagName = trim((string) ($config['tag_name'] ?? ''));

        if ($tagName === '') {
            return ['status' => 'failed', 'message' => 'tag_name is required for add_tag'];
        }

        $tag = Tag::withoutGlobalScopes()->firstOrCreate([
            'institution_id' => $lead->institution_id,
            'name' => $tagName,
        ], [
            'campus_id' => $lead->campus_id,
            'color' => isset($config['color']) ? (string) $config['color'] : null,
        ]);

        $lead->tags()->syncWithoutDetaching([
            $tag->id => ['institution_id' => $lead->institution_id],
        ]);

        return ['status' => 'success', 'message' => 'Tag added to lead'];
    }

    /** @param array<string, mixed> $config
     *  @return array{status:string,message:string,meta?:array<string,mixed>}
     */
    private function createTask(Lead $lead, array $config): array
    {
        $title = trim((string) ($config['title'] ?? ''));

        if ($title === '') {
            return ['status' => 'failed', 'message' => 'title is required for create_task'];
        }

        Task::create([
            'institution_id' => $lead->institution_id,
            'campus_id' => $lead->campus_id,
            'lead_id' => $lead->id,
            'assigned_to' => isset($config['assigned_to']) ? (int) $config['assigned_to'] : $lead->assigned_counsellor_id,
            'created_by' => isset($config['created_by']) ? (int) $config['created_by'] : null,
            'title' => $title,
            'description' => isset($config['description']) ? (string) $config['description'] : null,
            'status' => TaskStatus::OPEN->value,
            'due_at' => isset($config['due_at']) && is_string($config['due_at']) ? $config['due_at'] : null,
            'metadata' => [
                'source' => 'automation_workflow',
                'priority' => isset($config['priority']) ? (string) $config['priority'] : 'medium',
            ],
        ]);

        Activity::create([
            'institution_id' => $lead->institution_id,
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'type' => ActivityType::SYSTEM->value,
            'body' => 'Automation task created: '.$title,
            'metadata' => [
                'source' => 'workflow_action',
                'action_type' => 'create_task',
            ],
        ]);

        return ['status' => 'success', 'message' => 'Task created for lead'];
    }

    /**
     * BRD: CRM-MA-004 — Resolve A/B subject/content variant for automated email actions.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>|null
     */
    private function resolveAbTestVariant(Lead $lead, array $config): ?array
    {
        $abTest = $config['ab_test'] ?? null;
        if (! is_array($abTest) || ! (($abTest['enabled'] ?? false) === true)) {
            return null;
        }

        $variantsRaw = $abTest['variants'] ?? null;
        if (! is_array($variantsRaw) || count($variantsRaw) < 2) {
            return null;
        }

        $variants = array_values(array_filter($variantsRaw, static fn (mixed $variant): bool => is_array($variant)));
        if (count($variants) < 2) {
            return null;
        }

        $seed = sprintf('%s:%s:%s', (string) $lead->uuid, (string) $lead->id, (string) ($abTest['salt'] ?? 'ma-004'));
        $hash = abs((int) crc32($seed));
        $strategy = strtolower((string) ($abTest['strategy'] ?? 'split'));

        $selectedIndex = 0;

        if ($strategy === 'weighted') {
            $weights = array_map(static fn (array $variant): int => max(1, (int) ($variant['weight'] ?? 1)), $variants);
            $totalWeight = array_sum($weights);
            $bucket = $totalWeight > 0 ? $hash % $totalWeight : 0;

            $running = 0;
            foreach ($weights as $index => $weight) {
                $running += $weight;
                if ($bucket < $running) {
                    $selectedIndex = $index;
                    break;
                }
            }
        } else {
            $selectedIndex = $hash % count($variants);
        }

        $selectedVariant = $variants[$selectedIndex];

        $resolvedConfig = $config;
        foreach (['template_id', 'subject', 'custom_body_html', 'from_name', 'from_email'] as $key) {
            if (array_key_exists($key, $selectedVariant)) {
                $resolvedConfig[$key] = $selectedVariant[$key];
            }
        }

        return [
            'enabled' => true,
            'strategy' => $strategy,
            'selected_variant_index' => $selectedIndex,
            'selected_variant_id' => (string) ($selectedVariant['id'] ?? 'variant_'.$selectedIndex),
            'selected_variant_name' => isset($selectedVariant['name']) ? (string) $selectedVariant['name'] : null,
            'selected_subject' => isset($resolvedConfig['subject']) ? (string) $resolvedConfig['subject'] : null,
            'resolved_config' => $resolvedConfig,
        ];
    }
}
