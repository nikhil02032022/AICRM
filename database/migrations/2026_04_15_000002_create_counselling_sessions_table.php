<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EC-015 — Counselling sessions (appointments) table
// BRD: CRM-EC-016 — Public + internal appointment booking sessions
// BRD: CRM-EC-017 — Reminder flags stored here
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counselling_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('counsellor_id')->constrained('users');
            $table->char('availability_slot_id', 36)->nullable()->index();
            $table->foreign('availability_slot_id', 'cs_avail_slot_fk')
                ->references('id')
                ->on('counsellor_availability_slots')
                ->nullOnDelete();

            $table->string('session_type', 30); // CounsellingSessionType enum
            $table->string('status', 30)->default('scheduled'); // CounsellingSessionStatus enum
            $table->string('mode', 20)->default('online'); // online | offline | phone

            $table->dateTime('scheduled_at')->index();
            $table->dateTime('completed_at')->nullable();

            // Notes
            $table->text('pre_session_notes')->nullable();
            $table->text('post_session_notes')->nullable();  // No PII in Laravel logs

            // Reminder tracking (BRD: CRM-EC-017)
            $table->boolean('reminder_24h_sent')->default(false);
            $table->boolean('reminder_1h_sent')->default(false);

            // Public booking token (for /book/{slug} external flow)
            $table->string('booking_token', 64)->nullable()->unique();
            $table->dateTime('booking_token_expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'counsellor_id', 'scheduled_at'], 'sess_inst_couns_sched');
            $table->index(['institution_id', 'status'], 'sess_inst_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counselling_sessions');
    }
};
