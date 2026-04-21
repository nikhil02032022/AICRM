<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-001, CRM-FM-002 — Configurable fee amounts per programme/fee type
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('programme_id');

            $table->string('fee_type', 30); // FeeType enum
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');

            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('programme_id');
            $table->index(['institution_id', 'programme_id', 'fee_type', 'is_active'], 'fs_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
