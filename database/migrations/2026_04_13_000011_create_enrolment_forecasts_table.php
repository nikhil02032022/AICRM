<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-008 — Persist programme-wise enrolment forecast snapshots with confidence signals
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrolment_forecasts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('crm_programme_id')->nullable();
            $table->string('admission_cycle', 30)->nullable();
            $table->unsignedInteger('forecast_count');
            $table->unsignedTinyInteger('confidence_score');
            $table->json('inputs')->nullable();
            $table->string('model_version', 80)->default('a2a-forecast-rules-v1');
            $table->date('generated_for_month');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index('institution_id');
            $table->index('crm_programme_id');
            $table->index('generated_for_month');
            $table->index(['institution_id', 'generated_for_month'], 'ef_inst_month_idx');

            $table->foreign('crm_programme_id')
                ->references('id')
                ->on('crm_programmes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrolment_forecasts');
    }
};
