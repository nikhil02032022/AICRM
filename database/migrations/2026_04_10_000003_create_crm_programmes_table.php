<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EI-001 — Programme catalogue synced from A2A ERP; local cache table
// Stub migration — full ERP sync implemented in Phase 1 Sprint 3 (CRM-EI group)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_programmes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('institution_id');
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->string('level', 30)->nullable(); // UG, PG, Diploma, Certificate
            $table->string('department', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('erp_programme_uuid')->nullable()->unique(); // ERP reference
            $table->timestamps();

            $table->index('institution_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_programmes');
    }
};
