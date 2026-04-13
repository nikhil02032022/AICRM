<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-003 — Persist AI-assisted communication drafts by lead and channel
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_message_drafts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id');
            $table->string('channel', 30);
            $table->string('subject', 180)->nullable();
            $table->text('draft_text');
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->string('model_version', 80)->default('a2a-draft-rules-v1');
            $table->timestamp('generated_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index('channel');
            $table->index('generated_at');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_message_drafts');
    }
};
