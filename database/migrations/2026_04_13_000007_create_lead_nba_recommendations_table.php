<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-002 — Persist next best action recommendations per lead with reasoning
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_nba_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id');
            $table->string('recommended_action', 60);
            $table->text('reasoning');
            $table->unsignedTinyInteger('confidence_score');
            $table->json('channels')->nullable();
            $table->json('metadata')->nullable();
            $table->string('model_version', 80)->default('a2a-nba-rules-v1');
            $table->timestamp('generated_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index('recommended_action');
            $table->index('generated_at');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_nba_recommendations');
    }
};
