<?php

declare(strict_types=1);

namespace App\Livewire\CRM\Lead;

use App\DTOs\CRM\SendEmailDTO;
use App\Enums\CRM\CommunicationChannel;
use App\Models\CRM\CommunicationTemplate;
use App\Models\CRM\Lead;
use App\Models\CRM\SenderDomain;
use App\Services\CRM\Communication\EmailService;
use App\Services\CRM\Communication\TemplateService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

// BRD: CRM-CC-002 — Send individual email from a lead record
final class SendEmailModal extends Component
{
    public bool $showModal = false;

    public string $leadUuid = '';

    public int $leadId = 0;

    /** @var int|string */
    public int|string $templateId = 0;

    public string $customSubject = '';

    public string $customBodyHtml = '';

    public string $preview = '';

    public bool $isSubmitting = false;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(string $leadUuid, int $leadId): void
    {
        $this->leadUuid = $leadUuid;
        $this->leadId   = $leadId;
    }

    /** @return Collection<int, CommunicationTemplate> */
    #[Computed]
    public function emailTemplates(): Collection
    {
        return CommunicationTemplate::query()
            ->where('channel', CommunicationChannel::EMAIL)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'subject', 'body_html', 'body_text', 'type']);
    }

    #[Computed]
    public function defaultSender(): ?SenderDomain
    {
        return SenderDomain::query()
            ->where('is_default', true)
            ->where('spf_verified', true)
            ->where('dkim_verified', true)
            ->first();
    }

    #[On('open-send-email-modal')]
    public function openModal(): void
    {
        $this->reset(['templateId', 'customSubject', 'customBodyHtml', 'preview', 'successMessage', 'errorMessage']);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['templateId', 'customSubject', 'customBodyHtml', 'preview', 'successMessage', 'errorMessage']);
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

        $template = CommunicationTemplate::find((int) $this->templateId);
        if (!$template instanceof CommunicationTemplate) {
            $this->preview = '';

            return;
        }

        $lead = Lead::find($this->leadId);
        if (!$lead instanceof Lead) {
            $this->preview = '';

            return;
        }

        /** @var TemplateService $svc */
        $svc = app(TemplateService::class);

        $this->preview = $svc->render($template, [
            'first_name'       => $lead->first_name,
            'full_name'        => trim($lead->first_name.' '.($lead->last_name ?? '')),
            'institution_name' => '',
            'unsubscribe_link' => route('crm.unsubscribe', ['uuid' => $lead->uuid]),
        ]);
    }

    public function send(): void
    {
        $this->isSubmitting  = true;
        $this->successMessage = null;
        $this->errorMessage   = null;

        try {
            $lead = Lead::findOrFail($this->leadId);

            // BRD: CRM-CC-005 — Block if unsubscribed/DNC
            if ($lead->email_unsubscribed_at !== null || $lead->dnc_at !== null) {
                $this->errorMessage  = 'This lead has opted out of email communications.';
                $this->isSubmitting  = false;

                return;
            }

            if (empty($lead->email)) {
                $this->errorMessage = 'This lead does not have an email address on record.';
                $this->isSubmitting = false;

                return;
            }

            $sender = $this->defaultSender;

            $dto = new SendEmailDTO(
                templateId: (int) $this->templateId,
                fromName: $sender?->default_from_name ?? config('mail.from.name'),
                fromEmail: $sender?->default_from_email ?? config('mail.from.address'),
                subject: (int) $this->templateId > 0 ? null : ($this->customSubject ?: 'Message from us'),
                customBodyHtml: (int) $this->templateId === 0 ? ($this->customBodyHtml ?: null) : null,
            );

            /** @var EmailService $emailService */
            $emailService = app(EmailService::class);
            $emailService->sendToLead($lead, $dto);

            $this->successMessage = 'Email sent successfully.';
            $this->templateId     = 0;
            $this->customSubject  = '';
            $this->customBodyHtml = '';
            $this->preview        = '';

            $this->dispatch('email-sent');
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (\Throwable) {
            $this->errorMessage = 'Failed to send email. Please try again.';
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render(): View
    {
        return view('livewire.crm.lead.send-email-modal');
    }
}
