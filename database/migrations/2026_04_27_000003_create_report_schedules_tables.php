<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AR-020 — Scheduled report delivery: schedules and delivery audit log
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('custom_report_id');
            $table->unsignedBigInteger('created_by');       // FK users.id
            $table->string('name', 200);
            $table->string('frequency', 20);                // daily|weekly|monthly
            $table->tinyInteger('day_of_week')->nullable(); // 0=Sunday … 6=Saturday
            $table->tinyInteger('day_of_month')->nullable();// 1–28
            $table->string('run_time', 5)->default('07:00');// HH:MM in institution timezone
            $table->json('recipient_emails');               // array of email addresses
            $table->string('format', 10)->default('csv');  // csv|excel|pdf
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('custom_report_id');
            $table->index(['is_active', 'next_run_at']);

            $table->foreign('custom_report_id')
                ->references('id')
                ->on('custom_reports')
                ->cascadeOnDelete();
        });

        Schema::create('report_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('report_schedule_id');
            $table->unsignedBigInteger('custom_report_id');
            $table->string('status', 20)->default('queued');// ReportDeliveryStatus enum
            $table->json('recipient_emails');
            $table->string('format', 10);
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('institution_id');
            $table->index('report_schedule_id');
            $table->index(['status', 'created_at']);

            $table->foreign('report_schedule_id')
                ->references('id')
                ->on('report_schedules')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_deliveries');
        Schema::dropIfExists('report_schedules');
    }
};
