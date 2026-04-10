<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-001 — Shared communication templates for email, SMS, and WhatsApp
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->string('name'); // internal label
            $table->string('channel', 20); // CommunicationChannel enum: EMAIL, SMS, WHATSAPP
            $table->string('type', 30); // TemplateType enum: TRANSACTIONAL, MARKETING, OTP, NOTIFICATION
            $table->string('subject')->nullable(); // email only
            $table->longText('body_html')->nullable(); // email HTML
            $table->text('body_text'); // plain text / SMS / WhatsApp body
            $table->json('merge_tags')->nullable(); // list of available {{tag}} tokens
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('channel');
            $table->index('type');
            $table->index('is_active');
            $table->index(['institution_id', 'channel', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_templates');
    }
};
