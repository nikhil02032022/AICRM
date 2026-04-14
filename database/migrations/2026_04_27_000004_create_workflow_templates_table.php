<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-SA-007 — Workflow / automation template library
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            // null institution_id = global platform template (MEETCS-managed)
            $table->unsignedBigInteger('institution_id')->nullable();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 50);             // WorkflowTemplateCategory enum
            $table->string('trigger_type', 50);         // WorkflowNodeType enum value
            $table->json('template_data');              // full workflow steps definition (nodes + actions)
            $table->boolean('is_global')->default(false);// true = visible to all institutions
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['is_global', 'is_active', 'category']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_templates');
    }
};
