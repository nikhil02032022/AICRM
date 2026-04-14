<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EI-010 — LMS enrolment trigger log — tracks CamPLUS/Moodle auto-enrolment per student
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_enrolment_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            // Source: lead post-enrolment / admission confirmation
            $table->unsignedBigInteger('lead_id');
            $table->string('erp_student_id', 80)->nullable();     // A2A Student Master ID
            // LMS identifiers
            $table->string('lms_provider', 30)->nullable();       // camplus|moodle
            $table->string('lms_user_id', 80)->nullable();        // LMS-assigned user ID
            $table->string('lms_course_id', 80)->nullable();      // LMS course / programme ID
            $table->string('status', 30)->default('pending');     // LmsEnrolmentStatus enum
            $table->text('error_message')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            // Retry tracking
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['institution_id', 'lead_id']);
            $table->index(['institution_id', 'status']);
            $table->index('erp_student_id');
            $table->index('lms_provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_enrolment_logs');
    }
};
