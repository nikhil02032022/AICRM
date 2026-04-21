<?php

declare(strict_types=1);

use App\Jobs\CRM\EscalateUnactionedLeadsJob;
use App\Jobs\CRM\GenerateEnrolmentForecastJob;
use App\Jobs\CRM\GenerateDailyPriorityLeadListJob;
use App\Jobs\CRM\GenerateNbaJourneyJob;
use App\Jobs\CRM\RunAnomalyDetectionJob;
use App\Jobs\CRM\Automation\EvaluateEventBasedAutomationTriggersJob;
use App\Jobs\CRM\Automation\EvaluateInactivityAutomationTriggersJob;
use App\Jobs\CRM\Automation\EvaluateTimedAutomationTriggersJob;
use App\Jobs\CRM\SendAppointmentReminderJob;
use App\Services\CRM\Analytics\ReportSchedulerService;
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

// BRD: CRM-AI-005 — Generate daily counsellor priority lead lists at start of workday.
Schedule::job(new GenerateDailyPriorityLeadListJob(null, now()->toDateString()), 'ai')->dailyAt('06:00');

// BRD: CRM-AI-008 — Generate monthly programme-wise enrolment forecasts for planning dashboards.
Schedule::job(new GenerateEnrolmentForecastJob(null, now()->startOfMonth()->toDateString()), 'ai')->monthlyOn(1, '06:15');

// BRD: CRM-AI-009 — Detect anomaly drop-offs daily for managerial monitoring.
Schedule::job(new RunAnomalyDetectionJob(null, now()->toDateString(), 7, 28, 25), 'ai')->dailyAt('06:45');

// BRD: CRM-AI-010 — Generate segment-wise nurture journey suggestions daily for marketing orchestration.
Schedule::job(new GenerateNbaJourneyJob(null, now()->toDateString(), null), 'ai')->dailyAt('07:00');

// BRD: CRM-FM-010 — Dispatch due payment reminders every 15 minutes
Schedule::command('crm:payments:dispatch-reminders')->everyFifteenMinutes()
    ->name('crm.payments.dispatch-reminders')
    ->withoutOverlapping();

// BRD: CRM-DM-005 — Dispatch due document reminders every 15 minutes
Schedule::command('crm:documents:dispatch-reminders')->everyFifteenMinutes()
    ->name('crm.documents.dispatch-reminders')
    ->withoutOverlapping();

// BRD: CRM-FM-008 — Escalate stale scholarship approvals every 15 minutes
Schedule::command('crm:scholarships:dispatch-escalations')->everyFifteenMinutes()
    ->name('crm.scholarships.dispatch-escalations')
    ->withoutOverlapping();

// BRD: CRM-AR-020 — Process due scheduled report deliveries every 5 minutes
Schedule::call(fn () => app(ReportSchedulerService::class)->processDueSchedules())
    ->everyFiveMinutes()
    ->name('crm.report-scheduler.process-due')
    ->withoutOverlapping();
