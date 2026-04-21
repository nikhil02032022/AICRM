<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TF-004 — Overdue task escalation rules per institution
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_escalation_rules')) {
            return;
        }

        Schema::create('task_escalation_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedSmallInteger('overdue_threshold_hours')->default(24);
            $table->unsignedBigInteger('escalate_to_role_id')->nullable()->index();
            $table->string('notification_channel', 40)->default('both');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'is_active'], 'idx_escalation_rules_active');

            $table->foreign('escalate_to_role_id')
                ->references('id')
                ->on('roles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_escalation_rules');
    }
};
