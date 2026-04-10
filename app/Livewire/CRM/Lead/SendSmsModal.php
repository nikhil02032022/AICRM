<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Lead;

use App\Enums\CRM\DltTemplateStatus;
use App\Models\CRM\DltTemplate;
use App\Models\CRM\Lead;
use App\Services\CRM\Communication\SmsService;
use App\Services\CRM\Communication\TemplateService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

// BRD: CRM-CC-006 — Send individual SMS from a lead record
final class SendSmsModal extends Component
{
    public bool $showModal = false;

    public string $leadUuid = '';

    public int $leadId = 0;

    /** @var int|string */
    public int|string $templateId = 0;

    public string $preview = '';

    public bool $isSubmitting = false;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(string $leadUuid, int $leadId): void
    {
        $this->leadUuid = $leadUuid;
        $this->leadId   = $leadId;
    }

    /** @return Collection<int, DltTemplate> */
    #[Computed]
    public function smsTemplates(): Collection
    {
        return DltTemplate::query()
            ->where('institution_id', auth()->user()?->institution_id)
            ->where('status', DltTemplateStatus::APPROVED)
            ->orderBy('template_name')
            ->get(['id', 'template_name', 'template_body', 'gateway', 'sender_id']);
    }

    #[On('open-send-sms-modal')]
    public function openModal(): void
    {
        $this->reset(['templateId', 'preview', 'successMessage', 'errorMessage']);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['templateId', 'preview', 'successMessage', 'errorMessage']);
    }

    public function updatedTemplateId(): void
    {
        $this->errorMessage = null;
        $this->buildPreview();
    }

    public function buildPreview(): void
    {
        if ((int) $this->templateId === 0) {
            $this->preview = '';

            return;
        }

        $template = DltTemplate::find((int) $this->templateId);
        if (! $template instanceof DltTemplate) {
            $this->preview = '';

            return;
        }

        $lead = Lead::find($this->leadId);
        if (! $lead instanceof Lead) {
            $this->preview = '';

            return;
        }

        // Replace DLT {#var#} placeholders with lead data
        $body = $template->template_body;
        $vars = [
            trim($lead->first_name . ' ' . ($lead->last_name ?? '')),
        ];

        $offset = 0;
        $this->preview = preg_replace_callback('/\{#var#\}/', static function () use (&$offset, $vars): string {
            $value = $vars[$offset] ?? '';
            $offset++;

            return '<strong class="text-gray-900">' . e($value) . '</strong>';
        }, $body) ?? $body;
    }

    // BRD: CRM-CC-006 — Dispatch send via SmsService
    public function send(): void
    {
        $this->isSubmitting  = true;
        $this->errorMessage  = null;
        $this->successMessage = null;

        if ((int) $this->templateId === 0) {
            $this->errorMessage = 'Please select an approved DLT template.';
            $this->isSubmitting = false;

            return;
        }

        $template = DltTemplate::find((int) $this->templateId);
        if (! $template instanceof DltTemplate || ! $template->canSend()) {
            $this->errorMessage = 'Selected template is not approved for sending.';
            $this->isSubmitting = false;

            return;
        }

        $lead = Lead::find($this->leadId);
        if (! $lead instanceof Lead) {
            $this->errorMessage = 'Lead not found.';
            $this->isSubmitting = false;

            return;
        }

        /** @var TemplateService $templateService */
        $templateService = app(TemplateService::class);

        $message = $templateService->render(
            // Wrap DltTemplate body into a CommunicationTemplate-compatible render call
            // by substituting {#var#} directly
            tap(new \App\Models\CRM\CommunicationTemplate(), function ($ct) use ($template): void {
                $ct->body_text = $template->template_body;
                $ct->channel   = \App\Enums\CRM\CommunicationChannel::SMS;
            }),
            [
                'name'     => trim($lead->first_name . ' ' . ($lead->last_name ?? '')),
                'programme' => $lead->programmeInterests->first()?->name ?? '',
            ],
        );

        // Replace any remaining {#var#} tokens with lead's name as a fallback
        $message = preg_replace('/\{#var#\}/', trim($lead->first_name . ' ' . ($lead->last_name ?? '')), $message) ?? $message;

        try {
            /** @var SmsService $smsService */
            $smsService = app(SmsService::class);
            $smsService->sendToLead($lead, $message, $template);

            $this->successMessage = 'SMS sent successfully.';
            $this->templateId     = 0;
            $this->preview        = '';
        } catch (\Throwable $e) {
            $this->errorMessage = 'Failed to send SMS: ' . $e->getMessage();
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render(): View
    {
        return view('livewire.crm.lead.send-sms-modal');
    }
}
