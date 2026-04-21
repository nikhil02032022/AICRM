<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-009 — Configurable installment plans for initial fee
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_installment_plans', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('programme_id')->nullable();

            $table->string('name', 120);
            $table->string('fee_type', 30);       // FeeType enum
            $table->decimal('total_amount', 12, 2);
            $table->json('schedule');             // [{sequence, percent, due_offset_days, label}]
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('programme_id');
            $table->index(['institution_id', 'fee_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_installment_plans');
    }
};
