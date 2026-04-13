<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * PermissionSeeder — Seeds all CRM permissions (BRD §12).
 *
 * Grouped by CRM entity. Run before RoleSeeder (role bundles depend on these).
 */
class PermissionSeeder extends Seeder
{
    /**
     * All CRM permissions indexed by entity group.
     * Kept here as the single source of truth — referenced in tests.
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return [
            // Leads
            'crm.leads.view',
            'crm.leads.create',
            'crm.leads.edit',
            'crm.leads.delete',
            'crm.leads.assign',
            'crm.leads.export',
            'crm.leads.import',
            'crm.leads.merge',
            'crm.leads.view_pii',   // BRD: CRM-CR-002 — access to mobile/email/PII fields
            // Applications
            'crm.applications.view',
            'crm.applications.create',
            'crm.applications.edit',
            'crm.applications.delete',
            'crm.applications.convert',
            // Communications
            'crm.communications.send',
            'crm.communications.view',
            // Campaigns
            'crm.campaigns.manage',
            // Live Chat Widget (BRD: CRM-LC-006)
            'crm.chat-widget.manage',
            // Fees
            'crm.fees.view',
            'crm.fees.collect',
            'crm.fees.approve-discount',
            'crm.fees.refund',
            // Documents
            'crm.documents.view',
            'crm.documents.upload',
            'crm.documents.verify',
            // Tasks
            'crm.tasks.view',
            'crm.tasks.create',
            'crm.tasks.edit',
            // Reports
            'crm.reports.view',
            'crm.reports.export',
            // Users & Config
            'crm.users.manage',
            'crm.config.manage',
            // Agents
            'crm.agents.manage',
            // Web Forms (BRD: CRM-LC-001)
            'crm.forms.view',
            'crm.forms.create',
            'crm.forms.edit',
            'crm.forms.delete',
            // Integrations (BRD: CRM-SA-010)
            'crm.integrations.view',
            'crm.integrations.manage',
            // Counselling Sessions (BRD: CRM-EC-009)
            'crm.sessions.view',
            'crm.sessions.create',
            'crm.sessions.edit',
            'crm.sessions.cancel',
            // Settings / Assignment Config (BRD: CRM-EC-007)
            'crm.settings.manage',
            // Settings / Scoring Config (BRD: CRM-LQ-005)
            'crm.settings.scoring',
            // Group I Questionnaires (BRD: CRM-LQ-009)
            'crm.questionnaires.manage',
            'crm.questionnaires.respond',
            // Communication Engine — Group F (BRD: CRM-CC-001 to CRM-CC-025)
            'crm.communication.send',             // send/view individual comms + call log + WA inbox
            'crm.communication.templates.manage', // create/edit email/SMS/WA templates + DLT templates
            'crm.campaigns.send',                 // create + launch bulk email/SMS campaigns
        ];
    }

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->command->info('✅ '.count(self::permissions()).' CRM permissions seeded.');
    }
}
