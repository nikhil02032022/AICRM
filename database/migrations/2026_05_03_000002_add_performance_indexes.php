<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NFR-P-001, NFR-P-002 — Composite index additions for sub-3-second page loads.
// All operations are additive (no column changes) — safe for production with zero downtime.
return new class extends Migration
{
    public function up(): void
    {
        // leads: cover dashboard stat queries filtering by status + date range
        Schema::table('leads', function (Blueprint $table): void {
            $table->index(['institution_id', 'status', 'created_at'], 'leads_inst_status_created_idx');
            $table->index(['institution_id', 'assigned_counsellor_id', 'status'], 'leads_inst_counsellor_status_idx');
        });

        // applications: cover pipeline stage queries
        Schema::table('applications', function (Blueprint $table): void {
            $table->index(['institution_id', 'status', 'programme_id'], 'applications_inst_status_prog_idx');
        });

        // communication_logs: cover lead activity timeline queries ordered by date
        Schema::table('communication_logs', function (Blueprint $table): void {
            $table->index(['lead_id', 'created_at'], 'comm_logs_lead_created_idx');
        });

        // crm_tasks: cover counsellor task list queries filtered by assignee, due date, status
        Schema::table('crm_tasks', function (Blueprint $table): void {
            $table->index(['assigned_to', 'due_at', 'status'], 'tasks_assigned_due_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_inst_status_created_idx');
            $table->dropIndex('leads_inst_counsellor_status_idx');
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->dropIndex('applications_inst_status_prog_idx');
        });

        Schema::table('communication_logs', function (Blueprint $table): void {
            $table->dropIndex('comm_logs_lead_created_idx');
        });

        Schema::table('crm_tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_assigned_due_status_idx');
        });
    }
};
