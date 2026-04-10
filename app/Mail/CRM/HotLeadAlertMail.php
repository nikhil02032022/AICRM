<?php

declare(strict_types=1);

namespace App\Mail\CRM;

use App\Models\CRM\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LQ-006 — Email notification to counsellor when a lead reaches HOT temperature
final class HotLeadAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly object $counsellor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔥 HOT Lead Alert — {$this->lead->fullName()} (Score: {$this->lead->lead_score}/100)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.crm.hot-lead-alert',
            with: [
                'lead' => $this->lead,
                'counsellor' => $this->counsellor,
                'leadUrl' => route('crm.leads.show', $this->lead->uuid),
                'score' => $this->lead->lead_score,
                'temperature' => $this->lead->temperature?->label() ?? 'Hot',
                'source' => $this->lead->source?->label() ?? '—',
                'programme' => $this->lead->programmeInterests->first()?->name ?? 'Not specified',
            ],
        );
    }
}
