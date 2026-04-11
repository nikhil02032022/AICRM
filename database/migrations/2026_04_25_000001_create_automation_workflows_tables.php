<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_workflows', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('status', 40)->default('draft')->index();
            $table->string('trigger_type', 80);
            $table->json('trigger_config')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->foreignId('automation_workflow_id')->constrained('automation_workflows')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->string('node_type', 30);
            $table->string('name', 120);
            $table->json('config')->nullable();
            $table->unsignedInteger('delay_minutes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['automation_workflow_id', 'step_order'], 'workflow_steps_unique_order');
            $table->index(['automation_workflow_id', 'node_type']);
        });

        Schema::create('workflow_instances', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->foreignId('automation_workflow_id')->constrained('automation_workflows')->cascadeOnDelete();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->string('status', 40)->default('pending')->index();
            $table->unsignedBigInteger('current_workflow_step_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_action_executions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('action_type', 80)->nullable();
            $table->string('status', 40)->default('pending')->index();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workflow_instance_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_action_executions');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('automation_workflows');
    }
};
