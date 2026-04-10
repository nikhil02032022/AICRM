<?php

declare(strict_types=1);

namespace App\Mail\CRM;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-CC-002 — Mailable for individual and bulk email sends
final class LeadEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly string $fromName,
        public readonly string $fromEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($this->fromEmail, $this->fromName),
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->body,
        );
    }
}
