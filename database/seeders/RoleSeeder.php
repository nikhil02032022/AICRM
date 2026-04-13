<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// BRD: CRM-SA-002 — RBAC seeder: 11 roles with permission bundles.

/**
 * RoleSeeder — Seeds all 11 BRD roles (§12) and core CRM permissions.
 *
 * Roles (BRD §12):
 *   super-admin          — MEETCS: full platform access
 *   institution-admin    — Single institution: full access
 *   admissions-director  — Institution/Campus: management dashboards
 *   admissions-manager   — Team: reassignment + approvals
 *   senior-counsellor    — Own leads: communication + limited reports
 *   junior-counsellor    — Own leads: guided workflows, no fee approvals
 *   marketing-manager    — Campaign + lead source + marketing analytics
 *   finance-officer      — Fee management + payment reports
 *   document-verifier    — Document review and verification only
 *   agent                — Own leads: submission + status + commission
 *   applicant            — Own record: portal only
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions must be seeded first (via PermissionSeeder or DatabaseSeeder).
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ---------------------------------------------------------------
        // Roles with permission bundles
        // ---------------------------------------------------------------

        // super-admin gets all permissions (MEETCS support)
        Role::firstOrCreate(['name' => 'super-admin'])
            ->givePermissionTo(Permission::all());

        // institution-admin: full institution access
        Role::firstOrCreate(['name' => 'institution-admin'])
            ->givePermissionTo(Permission::all());

        // admissions-director: all CRM + reports
        Role::firstOrCreate(['name' => 'admissions-director'])
            ->givePermissionTo([
                'crm.leads.view', 'crm.leads.edit', 'crm.leads.assign',
                'crm.leads.export', 'crm.leads.import', 'crm.leads.merge',
                'crm.applications.view', 'crm.applications.edit', 'crm.applications.convert',
                'crm.communications.send', 'crm.communications.view',
                'crm.communication.send', 'crm.communication.templates.manage', 'crm.campaigns.send',
                'crm.campaigns.manage',
                'crm.voice.performance',
                // BRD: CRM-TC-009 — Directors can manage DNC list
                'crm.dnc.manage',
                'crm.fees.view', 'crm.fees.approve-discount',
                'crm.documents.view', 'crm.documents.verify',
                'crm.tasks.view', 'crm.tasks.create', 'crm.tasks.edit',
                'crm.reports.view', 'crm.reports.export',
                'crm.agents.manage',
                'crm.integrations.view',
                'crm.chat-widget.manage',
            ]);

        // admissions-manager: team-level management
        Role::firstOrCreate(['name' => 'admissions-manager'])
            ->givePermissionTo([
                'crm.leads.view', 'crm.leads.edit', 'crm.leads.assign',
                'crm.leads.export', 'crm.leads.import', 'crm.leads.merge',
                'crm.applications.view', 'crm.applications.edit',
                'crm.communications.send', 'crm.communications.view',
                'crm.communication.send', 'crm.communication.templates.manage', 'crm.campaigns.send',
                'crm.campaigns.manage',
                'crm.voice.performance',
                // BRD: CRM-TC-009 — Managers can manage DNC list
                'crm.dnc.manage',
                'crm.fees.view', 'crm.fees.approve-discount',
                'crm.documents.view', 'crm.documents.verify',
                'crm.tasks.view', 'crm.tasks.create', 'crm.tasks.edit',
                'crm.reports.view', 'crm.reports.export',
                'crm.integrations.view',
                'crm.chat-widget.manage',
            ]);

        // senior-counsellor: own leads + communication + limited reports
        Role::firstOrCreate(['name' => 'senior-counsellor'])
            ->givePermissionTo([
                'crm.leads.view', 'crm.leads.create', 'crm.leads.edit',
                'crm.applications.view', 'crm.applications.create', 'crm.applications.edit',
                'crm.communications.send', 'crm.communications.view',
                'crm.communication.send', 'crm.communication.templates.manage',
                'crm.fees.view',
                'crm.documents.view', 'crm.documents.upload',
                'crm.tasks.view', 'crm.tasks.create', 'crm.tasks.edit',
                'crm.reports.view',
            ]);

        // junior-counsellor: guided workflows, no fee approvals, no exports
        Role::firstOrCreate(['name' => 'junior-counsellor'])
            ->givePermissionTo([
                'crm.leads.view', 'crm.leads.create', 'crm.leads.edit',
                'crm.applications.view', 'crm.applications.create',
                'crm.communications.send', 'crm.communications.view',
                'crm.communication.send',
                'crm.documents.view', 'crm.documents.upload',
                'crm.tasks.view', 'crm.tasks.create', 'crm.tasks.edit',
            ]);

        // marketing-manager: campaigns + sources + analytics
        Role::firstOrCreate(['name' => 'marketing-manager'])
            ->givePermissionTo([
                'crm.leads.view', 'crm.leads.import', 'crm.leads.export',
                'crm.campaigns.manage',
                'crm.voice.performance',
                'crm.chat-widget.manage',
                'crm.communications.send', 'crm.communications.view',
                'crm.communication.send', 'crm.communication.templates.manage', 'crm.campaigns.send',
                'crm.reports.view', 'crm.reports.export',
            ]);

        // finance-officer: fee management + reports
        Role::firstOrCreate(['name' => 'finance-officer'])
            ->givePermissionTo([
                'crm.fees.view', 'crm.fees.collect', 'crm.fees.approve-discount', 'crm.fees.refund',
                'crm.reports.view', 'crm.reports.export',
            ]);

        // document-verifier: verification only
        Role::firstOrCreate(['name' => 'document-verifier'])
            ->givePermissionTo([
                'crm.documents.view',
                'crm.documents.verify',
            ]);

        // agent: own leads submission + status + commission
        Role::firstOrCreate(['name' => 'agent'])
            ->givePermissionTo([
                'crm.leads.view',
                'crm.leads.create',
            ]);

        // applicant: student portal — no CRM permissions (portal has its own guard)
        Role::firstOrCreate(['name' => 'applicant']);

        // ---------------------------------------------------------------
        // BRD: CRM-LC-001 — Web Form permissions assigned to relevant roles
        // ---------------------------------------------------------------
        $formManageRoles = ['super-admin', 'institution-admin', 'admissions-manager'];

        foreach ($formManageRoles as $roleName) {
            Role::findByName($roleName)
                ->givePermissionTo(['crm.forms.view', 'crm.forms.create', 'crm.forms.edit', 'crm.forms.delete']);
        }

        // Counsellors can view forms (to access public URL/QR) but not manage them
        foreach (['senior-counsellor', 'junior-counsellor', 'admissions-director'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo('crm.forms.view');
        }

        // ---------------------------------------------------------------
        // BRD: CRM-EC-009 — Counselling Session permissions
        // ---------------------------------------------------------------
        // Full session access: admin + director + manager
        foreach (['institution-admin', 'admissions-director', 'admissions-manager'] as $roleName) {
            Role::findByName($roleName)
                ->givePermissionTo(['crm.sessions.view', 'crm.sessions.create', 'crm.sessions.edit', 'crm.sessions.cancel']);
        }
        // Senior counsellor: can view, create, edit but not cancel
        Role::findByName('senior-counsellor')
            ->givePermissionTo(['crm.sessions.view', 'crm.sessions.create', 'crm.sessions.edit']);
        // Junior counsellor: view and create only
        Role::findByName('junior-counsellor')
            ->givePermissionTo(['crm.sessions.view', 'crm.sessions.create']);

        // ---------------------------------------------------------------
        // BRD: CRM-EC-007 — Assignment Config (settings) permissions
        // ---------------------------------------------------------------
        foreach (['institution-admin', 'admissions-director'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo('crm.settings.manage');
        }

        // ---------------------------------------------------------------
        // BRD: CRM-LQ-005 — Scoring Config settings permission
        // ---------------------------------------------------------------
        foreach (['institution-admin', 'admissions-director', 'admissions-manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo('crm.settings.scoring');
        }

        // ---------------------------------------------------------------
        // BRD: CRM-LQ-009 — Questionnaire management + response permissions
        // ---------------------------------------------------------------
        foreach (['institution-admin', 'admissions-director', 'admissions-manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo('crm.questionnaires.manage');
        }

        foreach (['institution-admin', 'admissions-director', 'admissions-manager', 'senior-counsellor', 'junior-counsellor'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo('crm.questionnaires.respond');
        }

        // ---------------------------------------------------------------
        // BRD: CRM-CC-004, CRM-CC-019 — Settings: Sender Domains + IVR
        // admissions-manager needs settings access to manage comms infrastructure
        // ---------------------------------------------------------------
        Role::findByName('admissions-manager')->givePermissionTo('crm.settings.manage');

        // ---------------------------------------------------------------
        // BRD: CRM-CC-001 to CRM-CC-023 — Communication Engine (Group F)
        // Sync permissions for roles that were seeded before Group F was added.
        // givePermissionTo() is idempotent (no duplicates).
        // ---------------------------------------------------------------
        $commFullRoles = ['super-admin', 'institution-admin', 'admissions-director', 'admissions-manager', 'marketing-manager'];
        foreach ($commFullRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'crm.communication.send',
                'crm.communication.templates.manage',
                'crm.campaigns.send',
            ]);
        }

        // Senior counsellors: send + manage templates (no bulk campaigns)
        Role::findByName('senior-counsellor')->givePermissionTo([
            'crm.communication.send',
            'crm.communication.templates.manage',
        ]);

        // Junior counsellors: send only
        Role::findByName('junior-counsellor')->givePermissionTo('crm.communication.send');

        $this->command->info('✅ 11 BRD roles and permissions seeded successfully.');
    }
}
