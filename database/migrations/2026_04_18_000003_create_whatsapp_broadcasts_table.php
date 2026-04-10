<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-015 — WhatsApp broadcast campaign persistence
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_broadcasts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('name', 120);
            $table->foreignId('template_id')->constrained('communication_templates')->restrictOnDelete();
            $table->json('recipient_filter')->nullable();
            $table->unsignedInteger('lead_count')->default(0);
            $table->unsignedInteger('dispatched_count')->default(0);
            $table->string('status', 20)->default('DRAFT');
            $table->timestamp('launched_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_broadcasts');
    }
};
