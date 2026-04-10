<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Models\CRM\CounsellingSession;
use App\Notifications\CRM\AppointmentReminderNotification;
use App\Repositories\CRM\Counselling\CounsellingSessionRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-EC-017 — Send 24h and 1h appointment reminder notifications
final class SendAppointmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct()
    {
        $this->onQueue('crm-notifications');
    }

    public function handle(CounsellingSessionRepositoryInterface $sessionRepository): void
    {
        // 24-hour reminders
        foreach ($sessionRepository->pendingReminders24h() as $session) {
            $this->sendReminder($session, '24h');
        }

        // 1-hour reminders
        foreach ($sessionRepository->pendingReminders1h() as $session) {
            $this->sendReminder($session, '1h');
        }
    }

    private function sendReminder(CounsellingSession $session, string $window): void
    {
        $lead = $session->lead;

        if (!$lead) {
            return;
        }

        $lead->notify(new AppointmentReminderNotification($session, $window));

        $field = $window === '24h' ? 'reminder_24h_sent' : 'reminder_1h_sent';
        $session->update([$field => true]);
    }
}
