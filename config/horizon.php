<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Name
    |--------------------------------------------------------------------------
    |
    | This name appears in notifications and in the Horizon UI. Unique names
    | can be useful while running multiple instances of Horizon within an
    | application, allowing you to identify the Horizon you're viewing.
    |
    */

    'name' => env('HORIZON_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    |
    | Silencing a job will instruct Horizon to not place the job in the list
    | of completed jobs within the Horizon dashboard. This setting may be
    | used to fully remove any noisy jobs from the completed jobs list.
    |
    */

    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],

    'silenced_tags' => [
        // 'notifications',
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with Horizon's
    | `horizon:snapshot` schedule to define how long to retain metrics.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon master
    | supervisor may consume before it is terminated and restarted. For
    | configuring these limits on your workers, see the next section.
    |
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */

    'defaults' => [
        // General CRM operations
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 2,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
        // Email, SMS, WhatsApp — isolated so AI jobs don't block comms
        'supervisor-communications' => [
            'connection' => 'redis',
            'queue' => ['communications'],
            'balance' => 'auto',
            'maxProcesses' => 5,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 90,
            'nice' => 0,
        ],
        // Anthropic API calls — longer timeout, limited concurrency
        'supervisor-ai' => [
            'connection' => 'redis',
            'queue' => ['ai'],
            'balance' => 'simple',
            'maxProcesses' => 3,
            'memory' => 256,
            'tries' => 2,
            'timeout' => 120,
            'nice' => 5,
        ],
        // CSV imports, bulk email blasts, bulk status updates
        'supervisor-bulk' => [
            'connection' => 'redis',
            'queue' => ['bulk-operations'],
            'balance' => 'simple',
            'maxProcesses' => 2,
            'memory' => 256,
            'tries' => 2,
            'timeout' => 300,
            'nice' => 10,
        ],
        // BRD: CRM-LQ-001, CRM-LQ-004 — Lead scoring recalculation (isolated queue)
        'supervisor-scoring' => [
            'connection' => 'redis',
            'queue' => ['crm-scoring'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 3,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 30,
            'nice' => 5,
        ],
        // BRD: CRM-LQ-006 — HOT lead notifications (high priority, fast — counsellor must be alerted immediately)
        'supervisor-notifications' => [
            'connection' => 'redis',
            'queue' => ['crm-notifications'],
            'balance' => 'auto',
            'maxProcesses' => 3,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 30,
            'nice' => 0,
        ],
        // BRD: CRM-LQ-006 — Cold lead → nurture drip sequence (Group F Communication Engine hook)
        'supervisor-nurture' => [
            'connection' => 'redis',
            'queue' => ['crm-nurture'],
            'balance' => 'simple',
            'maxProcesses' => 1,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 10,
        ],
        // BRD: CRM-CC-001 to CRM-CC-005 — Bulk email delivery (SendGrid / SES / Mailgun)
        'supervisor-comms-email' => [
            'connection' => 'redis',
            'queue' => ['crm-comms-email'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 90,
            'nice' => 2,
        ],
        // BRD: CRM-CC-006 to CRM-CC-010 — Bulk SMS delivery (MSG91 / Textlocal / Kaleyra)
        'supervisor-comms-sms' => [
            'connection' => 'redis',
            'queue' => ['crm-comms-sms'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 8,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 2,
        ],
        // BRD: CRM-CC-011 to CRM-CC-015 — WhatsApp inbound + broadcast (Meta Cloud API / BSPs)
        'supervisor-comms-whatsapp' => [
            'connection' => 'redis',
            'queue' => ['crm-comms-whatsapp'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 15,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 1,
        ],
        // BRD: CRM-CC-016 to CRM-CC-020 — Voice / IVR call processing (Exotel / Ozonetel / Knowlarity)
        'supervisor-comms-voice' => [
            'connection' => 'redis',
            'queue' => ['crm-comms-voice'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 6,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 2,
        ],
        // BRD: CRM-MA-002 — Marketing automation trigger evaluation queue
        'supervisor-automation' => [
            'connection' => 'redis',
            'queue' => ['crm-automation'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 3,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 4,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => ['maxProcesses' => 5, 'balanceMaxShift' => 2, 'balanceCooldown' => 3],
            'supervisor-communications' => ['maxProcesses' => 10, 'balanceMaxShift' => 3, 'balanceCooldown' => 3],
            'supervisor-ai' => ['maxProcesses' => 5],
            'supervisor-bulk' => ['maxProcesses' => 4],
            'supervisor-scoring' => ['maxProcesses' => 5, 'balanceMaxShift' => 2, 'balanceCooldown' => 3],
            'supervisor-notifications' => ['maxProcesses' => 5],
            'supervisor-nurture' => ['maxProcesses' => 2],
            'supervisor-comms-email' => ['maxProcesses' => 10, 'balanceMaxShift' => 3, 'balanceCooldown' => 2],
            'supervisor-comms-sms' => ['maxProcesses' => 8, 'balanceMaxShift' => 2, 'balanceCooldown' => 2],
            'supervisor-comms-whatsapp' => ['maxProcesses' => 15, 'balanceMaxShift' => 5, 'balanceCooldown' => 2],
            'supervisor-comms-voice' => ['maxProcesses' => 6, 'balanceMaxShift' => 2, 'balanceCooldown' => 3],
            'supervisor-automation' => ['maxProcesses' => 5, 'balanceMaxShift' => 2, 'balanceCooldown' => 3],
        ],

        'local' => [
            'supervisor-default' => ['maxProcesses' => 1],
            'supervisor-communications' => ['maxProcesses' => 1],
            'supervisor-ai' => ['maxProcesses' => 1],
            'supervisor-bulk' => ['maxProcesses' => 1],
            'supervisor-scoring' => ['maxProcesses' => 1],
            'supervisor-notifications' => ['maxProcesses' => 1],
            'supervisor-nurture' => ['maxProcesses' => 1],
            'supervisor-comms-email' => ['maxProcesses' => 1],
            'supervisor-comms-sms' => ['maxProcesses' => 1],
            'supervisor-comms-whatsapp' => ['maxProcesses' => 1],
            'supervisor-comms-voice' => ['maxProcesses' => 1],
            'supervisor-automation' => ['maxProcesses' => 1],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watcher Configuration
    |--------------------------------------------------------------------------
    |
    | The following list of directories and files will be watched when using
    | the `horizon:listen` command. Whenever any directories or files are
    | changed, Horizon will automatically restart to apply all changes.
    |
    */

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        'composer.json',
        '.env',
    ],
];
