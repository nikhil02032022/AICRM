<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TF-002 — Auto-create follow-up tasks from configurable inactivity rules
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_auto_rules')) {
            return;
        }

        Schema::create('task_auto_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->string('trigger_type', 40)->default('inactivity');
            $table->unsignedSmallInteger('inactivity_threshold_hours')->default(72);
            $table->string('task_type', 40)->default('call');
            $table->string('priority', 20)->default('normal');
            $table->string('assignee_strategy', 40)->default('lead_owner');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'is_active'], 'idx_auto_rules_active');
            $table->index(['institution_id', 'trigger_type'], 'idx_auto_rules_trigger');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_auto_rules');
    }
};
