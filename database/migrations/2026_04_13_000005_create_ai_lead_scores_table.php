<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LQ-003 — Persist AI-assisted scoring output with rationale and model metadata
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_lead_scores', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedTinyInteger('score');
            $table->text('explanation');
            $table->string('model_version', 80);
            $table->json('metadata')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index('calculated_at');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_lead_scores');
    }
};
