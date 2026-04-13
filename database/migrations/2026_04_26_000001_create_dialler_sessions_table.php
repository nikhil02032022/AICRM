<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TC-001 — Power/auto-dialler sessions for outbound campaign calling
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialler_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('campaign_name')->nullable();
            $table->string('status', 20)->default('QUEUED');
            $table->unsignedInteger('total_leads')->default(0);
            $table->unsignedInteger('queued_calls')->default(0);
            $table->unsignedInteger('placed_calls')->default(0);
            $table->unsignedInteger('skipped_calls')->default(0);
            $table->unsignedInteger('failed_calls')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_dialled_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'status']);
            $table->index(['started_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialler_sessions');
    }
};
