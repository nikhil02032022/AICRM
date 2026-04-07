<?php

declare(strict_types=1);

use App\Models\User;
use App\Domain\CRM\Models\Institution;
use App\Domain\CRM\Models\Campus;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RBAC Seeder Tests — BRD §12
 *
 * Verifies all 11 roles are created, all 32 permissions exist,
 * and role-permission bundles match BRD requirements.
 */
describe('PermissionSeeder', function (): void {
    beforeEach(function (): void {
        $this->seed([PermissionSeeder::class]);
    });

    it('seeds all 32 CRM permissions', function (): void {
        $expected = PermissionSeeder::permissions();

        foreach ($expected as $permission) {
            expect(Permission::where('name', $permission)->exists())->toBeTrue(
                "Permission [{$permission}] was not seeded."
            );
        }

        expect(Permission::count())->toBe(count($expected));
    });

    it('seeds permissions with web guard', function (): void {
        expect(Permission::where('guard_name', 'web')->count())
            ->toBe(count(PermissionSeeder::permissions()));
    });

    it('is idempotent — re-running does not duplicate permissions', function (): void {
        $this->seed([PermissionSeeder::class]);

        expect(Permission::count())->toBe(count(PermissionSeeder::permissions()));
    });
});

describe('RoleSeeder', function (): void {
    beforeEach(function (): void {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    });

    it('seeds all 11 BRD roles', function (): void {
        $roles = [
            'super-admin',
            'institution-admin',
            'admissions-director',
            'admissions-manager',
            'senior-counsellor',
            'junior-counsellor',
            'marketing-manager',
            'finance-officer',
            'document-verifier',
            'agent',
            'applicant',
        ];

        foreach ($roles as $role) {
            expect(Role::where('name', $role)->exists())->toBeTrue(
                "Role [{$role}] was not seeded."
            );
        }

        expect(Role::count())->toBe(count($roles));
    });

    it('super-admin has all permissions', function (): void {
        $role = Role::findByName('super-admin');
        expect($role->permissions->count())->toBe(count(PermissionSeeder::permissions()));
    });

    it('institution-admin has all permissions', function (): void {
        $role = Role::findByName('institution-admin');
        expect($role->permissions->count())->toBe(count(PermissionSeeder::permissions()));
    });

    it('junior-counsellor cannot approve-discount or export', function (): void {
        $role = Role::findByName('junior-counsellor');
        $permNames = $role->permissions->pluck('name');

        expect($permNames)->not->toContain('crm.fees.approve-discount')
            ->and($permNames)->not->toContain('crm.leads.export')
            ->and($permNames)->not->toContain('crm.reports.export');
    });

    it('finance-officer only has fee and report permissions', function (): void {
        $role = Role::findByName('finance-officer');
        $permNames = $role->permissions->pluck('name');

        expect($permNames)->toContain('crm.fees.collect')
            ->and($permNames)->toContain('crm.fees.refund')
            ->and($permNames)->not->toContain('crm.leads.create')
            ->and($permNames)->not->toContain('crm.users.manage');
    });

    it('document-verifier only has document permissions', function (): void {
        $role = Role::findByName('document-verifier');
        $permNames = $role->permissions->pluck('name');

        expect($permNames)->toContain('crm.documents.view')
            ->and($permNames)->toContain('crm.documents.verify')
            ->and($permNames)->not->toContain('crm.leads.create')
            ->and($permNames)->not->toContain('crm.fees.collect');
    });

    it('applicant has no CRM permissions', function (): void {
        $role = Role::findByName('applicant');
        expect($role->permissions->count())->toBe(0);
    });

    it('agent can only view and create leads', function (): void {
        $role = Role::findByName('agent');
        $permNames = $role->permissions->pluck('name');

        expect($permNames)->toContain('crm.leads.view')
            ->and($permNames)->toContain('crm.leads.create')
            ->and($permNames)->not->toContain('crm.leads.delete')
            ->and($permNames)->not->toContain('crm.leads.assign');
    });

    it('is idempotent — re-running does not duplicate roles', function (): void {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        expect(Role::count())->toBe(11);
    });
});

describe('UserSeeder', function (): void {
    beforeEach(function (): void {
        $this->seed([PermissionSeeder::class, RoleSeeder::class, UserSeeder::class]);
    });

    it('seeds 11 test users', function (): void {
        expect(User::count())->toBe(11);
    });

    it('seeds demo institution and campus', function (): void {
        expect(Institution::where('code', 'DEMO')->exists())->toBeTrue();
        expect(Campus::where('code', 'MAIN')->exists())->toBeTrue();
    });

    it('super-admin has no institution_id', function (): void {
        $user = User::where('email', 'superadmin@a2acrm.test')->firstOrFail();
        expect($user->institution_id)->toBeNull();
    });

    it('all non-super-admin users have institution_id', function (): void {
        User::where('email', '!=', 'superadmin@a2acrm.test')->each(function (User $user): void {
            expect($user->institution_id)->not->toBeNull(
                "User [{$user->email}] is missing institution_id."
            );
        });
    });

    it('each user has exactly one role', function (): void {
        User::all()->each(function (User $user): void {
            expect($user->roles->count())->toBe(1,
                "User [{$user->email}] does not have exactly one role."
            );
        });
    });

    it('is idempotent — re-running does not duplicate users', function (): void {
        $this->seed([UserSeeder::class]);
        expect(User::count())->toBe(11);
    });
});
