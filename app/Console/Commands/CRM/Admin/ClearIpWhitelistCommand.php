<?php

declare(strict_types=1);

namespace App\Console\Commands\CRM\Admin;

use App\Models\CRM\Institution;
use App\Services\CRM\Admin\SystemConfigService;
use Illuminate\Console\Command;

// NFR-SE-005 — Emergency CLI to clear IP whitelist when admins are accidentally locked out.
// Logs the action to the standard Laravel log for audit trail.
final class ClearIpWhitelistCommand extends Command
{
    protected $signature = 'crm:admin:clear-ip-whitelist
                            {--institution= : Institution ID to clear (clears all if omitted)}';

    protected $description = 'Emergency: clear the admin IP whitelist to restore access when locked out';

    public function handle(SystemConfigService $configService): int
    {
        $institutionId = $this->option('institution');

        if ($institutionId) {
            $configService->set('admin_ip_whitelist', '', 'string', (int) $institutionId);
            $this->info("IP whitelist cleared for institution {$institutionId}.");
            logger()->warning('crm:admin:clear-ip-whitelist executed', ['institution_id' => $institutionId]);
        } else {
            $institutions = Institution::query()->pluck('id');

            foreach ($institutions as $id) {
                $configService->set('admin_ip_whitelist', '', 'string', $id);
            }

            $this->info("IP whitelist cleared for all {$institutions->count()} institutions.");
            logger()->warning('crm:admin:clear-ip-whitelist executed for ALL institutions', ['count' => $institutions->count()]);
        }

        return self::SUCCESS;
    }
}
