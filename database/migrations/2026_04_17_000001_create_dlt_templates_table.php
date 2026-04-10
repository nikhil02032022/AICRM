<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-008 — DLT template registration workflow for SMS (TRAI compliance)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dlt_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('sender_id', 10); // 6-char DLT registered sender e.g. "ACCADM"
            $table->string('template_name');
            $table->string('dlt_template_id')->nullable(); // TRAI/DLT issued ID, null until approved
            $table->text('template_body'); // approved DLT message body with {#var#} variables
            $table->string('message_type', 20); // DltMessageType enum: TRANSACTIONAL, PROMOTIONAL, OTP, SERVICE
            $table->string('gateway', 20); // SmsGateway enum: MSG91, TEXTLOCAL, KALEYRA
            $table->string('status', 30)->default('DRAFT'); // DltTemplateStatus enum
            $table->text('approval_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('gateway');
            $table->index('status');
            $table->index(['institution_id', 'gateway', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dlt_templates');
    }
};
