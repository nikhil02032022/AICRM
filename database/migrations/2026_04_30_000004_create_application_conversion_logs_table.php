<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-016, CRM-AP-017 — ERP Student Master conversion tracking (lead → student)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_conversion_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();

            // Relationships
            $table->uuid('application_uuid');
            $table->uuid('lead_uuid');
            $table->string('erp_student_id', 100)->nullable(); // Returned by ERP on success
            $table->unsignedBigInteger('converted_by_user_id')->nullable();

            // Conversion tracking
            $table->string('status', 30)->default('pending'); // pending, success, failed
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Request/response for audit trail
            $table->json('conversion_payload')->nullable(); // Sent to ERP
            $table->json('erp_response')->nullable(); // Received from ERP
            $table->text('error_message')->nullable(); // Failure reason

            // For idempotent retries
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for lookup and retry scheduling
            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('application_uuid');
            $table->index('lead_uuid');
            $table->index('status');
            $table->index('erp_student_id');
            $table->index('converted_by_user_id');
            $table->index('next_retry_at');
            $table->index('completed_at');
            $table->index('created_at');
            $table->index(['institution_id', 'status']);
            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_conversion_logs');
    }
};
