<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-001 — Configurable multi-step application form builder
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_form_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();

            $table->string('name', 150);
            $table->string('slug', 120);
            $table->text('description')->nullable();

            // BRD: CRM-AP-001 — section, field and logic configuration
            $table->json('sections');
            $table->json('progression_rules')->nullable();
            $table->json('settings')->nullable();

            // BRD: CRM-AP-007 readiness — completeness threshold configuration
            $table->unsignedTinyInteger('minimum_completeness_percentage')->default(100);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('slug');
            $table->index('is_active');
            $table->index('created_at');
            $table->index(['institution_id', 'is_active']);
            $table->index(['institution_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_form_templates');
    }
};
