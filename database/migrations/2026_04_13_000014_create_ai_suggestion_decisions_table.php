<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-011 — Persist human Accept/Edit/Dismiss decisions for AI-generated suggestions
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_suggestion_decisions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->string('suggestion_type', 60);
            $table->uuid('suggestion_uuid')->nullable()->index();
            $table->string('decision', 20);
            $table->longText('edited_content')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('acted_by');
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->index('institution_id');
            $table->index('suggestion_type');
            $table->index('decision');
            $table->index('acted_at');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();

            $table->foreign('acted_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_suggestion_decisions');
    }
};
