<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-004 — Persist inbound sentiment snapshots for lead prioritisation
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sentiment_flags', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id');
            $table->string('channel', 30)->nullable();
            $table->string('sentiment_label', 20);
            $table->integer('sentiment_score');
            $table->boolean('is_urgent')->default(false);
            $table->text('rationale');
            $table->string('source_excerpt', 500)->nullable();
            $table->json('indicators')->nullable();
            $table->string('model_version', 80)->default('a2a-sentiment-rules-v1');
            $table->timestamp('flagged_at');
            $table->timestamps();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index('sentiment_label');
            $table->index('is_urgent');
            $table->index('flagged_at');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentiment_flags');
    }
};
