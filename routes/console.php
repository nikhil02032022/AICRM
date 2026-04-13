<?php

declare(strict_types=1);

use App\Jobs\CRM\EscalateUnactionedLeadsJob;
use App\Jobs\CRM\Automation\EvaluateEventBasedAutomationTriggersJob;
use App\Jobs\CRM\Automation\EvaluateInactivityAutomationTriggersJob;
use App\Jobs\CRM\Automation\EvaluateTimedAutomationTriggersJob;
use App\Jobs\CRM\SendAppointmentReminderJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// BRD: CRM-EC-009 — Check for unactioned leads past escalation threshold every hour
Schedule::job(EscalateUnactionedLeadsJob::class, 'crm-notifications')->hourly();

// BRD: CRM-EC-017 — Send appointment reminders (24h and 1h windows) every 30 minutes
Schedule::job(SendAppointmentReminderJob::class, 'crm-notifications')->everyThirtyMinutes();

// BRD: CRM-MA-002 — Evaluate date/time automation triggers every 15 minutes
Schedule::job(EvaluateTimedAutomationTriggersJob::class, 'crm-automation')->everyFifteenMinutes();

// BRD: CRM-MA-009 — Evaluate event-based automation triggers every 15 minutes
Schedule::job(EvaluateEventBasedAutomationTriggersJob::class, 'crm-automation')->everyFifteenMinutes();

// BRD: CRM-MA-002 — Evaluate inactivity timeout automation triggers daily
Schedule::job(EvaluateInactivityAutomationTriggersJob::class, 'crm-automation')->daily();
