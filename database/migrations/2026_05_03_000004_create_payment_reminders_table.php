<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-010 — Automated payment reminders before due dates
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reminders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('payment_transaction_id');

            $table->timestamp('due_at');
            $table->timestamp('scheduled_for');
            $table->string('channel', 20);
            $table->string('status', 20)->default('pending');
            $table->boolean('opted_out')->default(false);

            $table->timestamp('sent_at')->nullable();
            $table->string('failure_reason', 255)->nullable();

            $table->timestamps();

            $table->index('institution_id');
            $table->index(['status', 'scheduled_for']);
            $table->index('payment_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
    }
};
