<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-DM-005 — Automated reminders for pending documents
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_reminders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('application_document_id');

            $table->timestamp('scheduled_for');
            $table->string('channel', 20);
            $table->string('status', 20)->default('pending'); // DocumentReminderStatus
            $table->boolean('opted_out')->default(false);

            $table->timestamp('sent_at')->nullable();
            $table->string('failure_reason', 255)->nullable();

            $table->timestamps();

            $table->index('institution_id');
            $table->index(['status', 'scheduled_for']);
            $table->index('application_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_reminders');
    }
};
