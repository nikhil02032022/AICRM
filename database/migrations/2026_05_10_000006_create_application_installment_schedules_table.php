<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-009 — Per-application installment schedule rows
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_installment_schedules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->uuid('application_uuid');
            $table->unsignedBigInteger('fee_installment_plan_id');

            $table->unsignedSmallInteger('sequence');
            $table->string('label', 120)->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('status', 20)->default('pending'); // InstallmentStatus
            $table->uuid('payment_transaction_uuid')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('application_uuid');
            $table->index(['application_uuid', 'status']);
            $table->unique(['application_uuid', 'fee_installment_plan_id', 'sequence'], 'ais_app_plan_seq_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_installment_schedules');
    }
};
