<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-013 — Track CRM→ERP fee migration on enrolment conversion
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_conversion_logs', function (Blueprint $table): void {
            $table->string('fee_migration_status', 30)->nullable()->after('onboarding_status');
            $table->timestamp('fee_migration_attempted_at')->nullable()->after('fee_migration_status');
            $table->timestamp('fee_migration_completed_at')->nullable()->after('fee_migration_attempted_at');
            $table->string('fee_migration_error', 255)->nullable()->after('fee_migration_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('application_conversion_logs', function (Blueprint $table): void {
            $table->dropColumn([
                'fee_migration_status',
                'fee_migration_attempted_at',
                'fee_migration_completed_at',
                'fee_migration_error',
            ]);
        });
    }
};
