<?php

declare(strict_types=1);

namespace App\Mail\CRM;

use App\Models\CRM\LeadImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-012 — Email notification sent to the user who initiated a bulk import
final class ImportCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly LeadImportBatch $batch,
        public readonly int             $successful,
        public readonly int             $failed,
        public readonly bool            $hasErrorReport,
        public readonly string          $subject,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subject);
    }

    public function content(): Content
    {
        $errorReportUrl = ($this->hasErrorReport && $this->batch->error_report_path !== null)
            ? route('crm.imports.report', ['batch' => $this->batch->uuid])
            : null;

        return new Content(
            view: 'emails.crm.import-completed',
            with: [
                'batch'          => $this->batch,
                'successful'     => $this->successful,
                'failed'         => $this->failed,
                'errorReportUrl' => $errorReportUrl,
            ],
        );
    }
}
