<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-008 — Approval chain award record
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_awards', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->uuid('application_uuid');
            $table->uuid('lead_uuid')->nullable();
            $table->unsignedBigInteger('scholarship_category_id');

            $table->decimal('amount', 12, 2);
            $table->string('status', 30)->default('draft'); // ScholarshipAwardStatus
            $table->string('current_stage', 20)->default('counsellor'); // ApprovalStage

            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('rejection_reason', 500)->nullable();

            $table->timestamp('counsellor_submitted_at')->nullable();
            $table->timestamp('manager_approved_at')->nullable();
            $table->timestamp('finance_approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('application_uuid');
            $table->index('lead_uuid');
            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'current_stage', 'status'], 'sa_stage_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_awards');
    }
};
