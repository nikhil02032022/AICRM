<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Compliance;

use App\Models\CRM\Compliance\SecurityIncident;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

// BRD: CRM-CR-010 — Breach notification within 72h of detected breach
class BreachNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly SecurityIncident $incident)
    {
        $this->onQueue('crm-compliance');
    }

    public function handle(): void
    {
        $admins = User::withoutGlobalScopes()
            ->where('institution_id', $this->incident->institution_id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['institution-admin', 'super-admin']))
            ->get();

        foreach ($admins as $admin) {
            Mail::send([], [], function (Message $message) use ($admin) {
                $message->to($admin->email, $admin->name)
                    ->subject('[URGENT] Security Breach Notification — Action Required within 72 Hours')
                    ->html($this->buildEmailBody($admin));
            });
        }

        $docs = $this->incident->documentation_json ?? [];
        $docs['breach_notification_sent_at'] = now()->toIso8601String();
        $docs['notified_admin_count']        = $admins->count();

        $this->incident->updateQuietly(['documentation_json' => $docs]);
    }

    private function buildEmailBody(User $admin): string
    {
        return sprintf(
            '<p>Dear %s,</p>
            <p>A security incident has been reported for your institution.</p>
            <p><strong>Type:</strong> %s</p>
            <p><strong>Detected At:</strong> %s</p>
            <p><strong>Description:</strong> %s</p>
            <p>Please log in to the CRM and review the incident in the Compliance module.</p>
            <p>This notification is in accordance with the DPDP Act 2023 (72-hour breach notification requirement).</p>',
            htmlspecialchars($admin->name),
            htmlspecialchars($this->incident->incident_type),
            $this->incident->detected_at?->format('d M Y H:i'),
            htmlspecialchars($this->incident->description)
        );
    }
}
