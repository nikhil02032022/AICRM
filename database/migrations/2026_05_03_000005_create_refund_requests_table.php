<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-011 — Refund request workflow for withdrawn applicants
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('payment_transaction_id');

            $table->unsignedBigInteger('requested_by');
            $table->text('reason');
            $table->decimal('amount', 12, 2);

            $table->string('status', 30)->default('pending');
            $table->json('approver_chain')->nullable();

            $table->string('gateway_refund_id', 120)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('failure_reason', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('payment_transaction_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
