<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-008, CRM-AP-009 — Application pipeline entity with status tracking
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();

            // Relationships
            $table->uuid('lead_uuid');
            $table->uuid('application_form_draft_uuid');
            $table->uuid('admission_cycle_uuid')->nullable();
            $table->unsignedBigInteger('assigned_counsellor_id')->nullable();

            // Pipeline state
            $table->string('status', 30)->default('under_review');
            $table->timestamp('stage_entered_at')->nullable();
            $table->timestamp('submitted_at');

            // Soft deletes & timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for multi-tenancy, filtering, performance
            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('lead_uuid');
            $table->index('status');
            $table->index('assigned_counsellor_id');
            $table->index('stage_entered_at');
            $table->index('created_at');
            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'assigned_counsellor_id']);
            $table->index(['institution_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
