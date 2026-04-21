<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TF-001 to CRM-TF-009 — Expand crm_tasks with type, priority, disposition, source
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('crm_tasks', 'type')) {
            Schema::table('crm_tasks', function (Blueprint $table): void {
                $table->string('type', 40)->nullable()->after('title')->index();
                $table->string('priority', 20)->default('normal')->after('type')->index();
                $table->string('disposition', 60)->nullable()->after('status');
                $table->string('source', 20)->default('manual')->after('disposition')->index();
                $table->unsignedBigInteger('auto_rule_id')->nullable()->after('source')->index();
                $table->timestamp('completed_at')->nullable()->after('due_at');
                $table->timestamp('overdue_flagged_at')->nullable()->after('completed_at');

                $table->index(['institution_id', 'assigned_to', 'status', 'due_at'], 'idx_tasks_manager_dashboard');
                $table->index(['institution_id', 'source', 'status'], 'idx_tasks_auto_rule_dedup');
            });
        }

        if (!Schema::hasColumn('leads', 'last_contact_at')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->timestamp('last_contact_at')->nullable()->after('updated_at')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            if (Schema::hasColumn('leads', 'last_contact_at')) {
                $table->dropIndex(['last_contact_at']);
                $table->dropColumn('last_contact_at');
            }
        });

        if (!Schema::hasColumn('crm_tasks', 'type')) {
            return;
        }

        Schema::table('crm_tasks', function (Blueprint $table): void {
            $table->dropIndex('idx_tasks_manager_dashboard');
            $table->dropIndex('idx_tasks_auto_rule_dedup');
            $table->dropIndex(['auto_rule_id']);
            $table->dropIndex(['source']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['type']);
            $table->dropColumn([
                'type', 'priority', 'disposition', 'source',
                'auto_rule_id', 'completed_at', 'overdue_flagged_at',
            ]);
        });
    }
};
