<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Documents;

use App\Enums\CRM\Documents\DocumentStatus;
use App\Models\CRM\Documents\ApplicationDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-DM-004
class DocumentDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ApplicationDocument $document) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verb = $this->document->status === DocumentStatus::VERIFIED ? 'verified' : 'rejected';

        $mail = (new MailMessage)
            ->subject("Document {$verb}")
            ->line("Your document has been {$verb}.");

        if ($this->document->status === DocumentStatus::REJECTED && $this->document->rejection_reason) {
            $mail->line('Reason: '.$this->document->rejection_reason);
            $mail->line('Please re-upload a corrected copy.');
        }

        return $mail;
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'document_uuid'    => $this->document->uuid,
            'status'           => $this->document->status?->value,
            'rejection_reason' => $this->document->rejection_reason,
        ];
    }
}
