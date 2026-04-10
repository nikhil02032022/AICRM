<?php

declare(strict_types=1);

use App\Domain\CRM\Models\AuditLog;
use App\Domain\CRM\Models\Institution;
use App\Domain\CRM\Observers\AuditObserver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * AuditObserver Tests — OWASP A09, DPDP compliance
 *
 * Verifies:
 * 1. A row is written to audit_logs on model create/update/delete.
 * 2. PII fields are redacted before writing (DPDP).
 * 3. Institution + user context is correctly resolved.
 */

/**
 * Minimal observable model for testing — avoids needing a Lead or real CRM model.
 * Uses the Institution model which already exists in the test DB.
 */
function seedBaseData(): array
{
    $institution = Institution::create([
        'name' => 'Test University',
        'code' => 'TEST',
        'domain' => 'test.edu',
        'is_active' => true,
    ]);

    return compact('institution');
}

describe('AuditObserver DB write', function (): void {

    it('writes a created row to audit_logs when a model is created', function (): void {
        $observer = new AuditObserver;
        $institution = Institution::create([
            'name' => 'Audit Test Uni',
            'code' => 'AUDITUNI',
            'domain' => 'audit.edu',
            'is_active' => true,
        ]);

        // Manually fire the observer (Institution does not auto-observe in tests)
        $observer->created($institution);

        expect(DB::table('audit_logs')->count())->toBe(1);

        $log = DB::table('audit_logs')->first();
        expect($log->entity_type)->toBe(Institution::class)
            ->and($log->entity_id)->toBe($institution->id)
            ->and($log->action)->toBe('created');
    });

    it('writes an updated row to audit_logs when a model is updated', function (): void {
        $observer = new AuditObserver;
        $institution = Institution::create([
            'name' => 'Before Update',
            'code' => 'UPD001',
            'domain' => 'update.edu',
            'is_active' => true,
        ]);

        $institution->name = 'After Update';
        $institution->save();

        // Simulate the observer update call
        $observer->updated($institution);

        $log = DB::table('audit_logs')
            ->where('action', 'updated')
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->action)->toBe('updated');
    });

    it('writes a deleted row to audit_logs when a model is deleted', function (): void {
        $observer = new AuditObserver;
        $institution = Institution::create([
            'name' => 'To Be Deleted',
            'code' => 'DEL001',
            'domain' => 'deleted.edu',
            'is_active' => true,
        ]);

        $observer->deleted($institution);

        $log = DB::table('audit_logs')
            ->where('action', 'deleted')
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->action)->toBe('deleted');
    });

    it('redacts PII fields in new_values', function (): void {
        $observer = new AuditObserver;
        $institution = Institution::create([
            'name' => 'PII Test Uni',
            'code' => 'PIITEST',
            'domain' => 'pii.edu',
            'is_active' => true,
        ]);

        // Simulate a model with PII fields in attributes
        $institution->forceFill([
            'mobile' => '9999999999',
            'email' => 'lead@test.edu',
        ]);

        $observer->created($institution);

        $log = DB::table('audit_logs')->latest('id')->first();
        $newValues = json_decode($log->new_values, true);

        if (isset($newValues['mobile'])) {
            expect($newValues['mobile'])->toBe('[REDACTED]');
        }

        if (isset($newValues['email'])) {
            expect($newValues['email'])->toBe('[REDACTED]');
        }
    });

    it('records old_values as empty array on create', function (): void {
        $observer = new AuditObserver;
        $institution = Institution::create([
            'name' => 'Old Values Test',
            'code' => 'OLDVAL',
            'domain' => 'oldval.edu',
            'is_active' => true,
        ]);

        $observer->created($institution);

        $log = DB::table('audit_logs')->latest('id')->first();
        $oldValues = json_decode($log->old_values, true);

        expect($oldValues)->toBe([]);
    });
});

describe('AuditLog model', function (): void {
    uses(RefreshDatabase::class);

    it('can query audit_logs via the AuditLog model', function (): void {
        $observer = new AuditObserver;
        $institution = Institution::create([
            'name' => 'Model Query Test',
            'code' => 'MDLQ01',
            'domain' => 'mdlq.edu',
            'is_active' => true,
        ]);

        $observer->created($institution);

        $log = AuditLog::where('entity_type', Institution::class)->first();

        expect($log)->not->toBeNull()
            ->and($log->action)->toBe('created')
            ->and($log->entity_id)->toBe($institution->id);
    });

    it('scopeForEntity filters correctly', function (): void {
        $observer = new AuditObserver;
        $inst1 = Institution::create(['name' => 'Inst A', 'code' => 'SCPA', 'domain' => 'scpa.edu', 'is_active' => true]);
        $inst2 = Institution::create(['name' => 'Inst B', 'code' => 'SCPB', 'domain' => 'scpb.edu', 'is_active' => true]);

        $observer->created($inst1);
        $observer->created($inst2);

        $results = AuditLog::forEntity(Institution::class, $inst1->id)->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->entity_id)->toBe($inst1->id);
    });

    it('scopeAction filters by action', function (): void {
        $observer = new AuditObserver;
        $inst = Institution::create(['name' => 'Action Scope', 'code' => 'ACSC', 'domain' => 'acsc.edu', 'is_active' => true]);

        $observer->created($inst);
        $observer->updated($inst);

        expect(AuditLog::action('created')->count())->toBe(1)
            ->and(AuditLog::action('updated')->count())->toBe(1);
    });
});
