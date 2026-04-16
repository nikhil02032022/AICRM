<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-003 — Save-and-resume draft persistence for application forms
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_form_drafts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('application_form_template_id');

            $table->string('resume_token', 80)->unique();
            $table->string('status', 30)->default('draft');
            $table->string('current_section_id', 60)->nullable();
            $table->unsignedTinyInteger('last_completed_section_order')->nullable();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->longText('form_data')->nullable();

            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('application_form_template_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index('created_at');
            $table->index(['institution_id', 'status'], 'afd_inst_status_idx');
            $table->index(['institution_id', 'application_form_template_id'], 'afd_inst_tpl_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_form_drafts');
    }
};
