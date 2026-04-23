<?php

declare(strict_types=1);

// BRD: CRM-AR-007 — DashboardScopeService unit tests: role-based scope resolution

use App\Models\CRM\Institution;
use App\Models\User;
use App\Services\CRM\Analytics\DashboardScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('DashboardScopeService (CRM-AR-007)', function () {

    beforeEach(function () {
        $this->institution = Institution::factory()->create();
        $this->service     = new DashboardScopeService;

        // Ensure roles exist for test assertions
        foreach (['counsellor', 'senior-counsellor', 'admissions_manager', 'admissions_director', 'institution-admin', 'super-admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    });

    it('returns counsellor scope with own id only', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('counsellor');

        $scope = $this->service->resolveScope($user);

        expect($scope['role'])->toBe('counsellor')
            ->and($scope['counsellor_ids'])->toBe([$user->id])
            ->and($scope['institution_id'])->toBe((int) $this->institution->id);
    });

    it('returns manager scope with null counsellor_ids', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_manager');

        $scope = $this->service->resolveScope($user);

        expect($scope['role'])->toBe('manager')
            ->and($scope['counsellor_ids'])->toBeNull();
    });

    it('returns director scope with null counsellor_ids and null campus', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('admissions_director');

        $scope = $this->service->resolveScope($user);

        expect($scope['role'])->toBe('director')
            ->and($scope['counsellor_ids'])->toBeNull()
            ->and($scope['campus_id'])->toBeNull();
    });

    it('institution-admin gets director-equivalent scope', function () {
        $user = User::factory()->for($this->institution)->create();
        $user->assignRole('institution-admin');

        $scope = $this->service->resolveScope($user);

        expect($scope['role'])->toBe('director');
    });

    it('isInstitutionWide returns true for null counsellor_ids', function () {
        $scope = ['counsellor_ids' => null];
        expect($this->service->isInstitutionWide($scope))->toBeTrue();
    });

    it('isInstitutionWide returns false for specific counsellor_ids', function () {
        $scope = ['counsellor_ids' => [1]];
        expect($this->service->isInstitutionWide($scope))->toBeFalse();
    });
});
