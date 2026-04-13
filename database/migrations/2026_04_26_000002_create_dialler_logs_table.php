<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TC-001 — Queue entries for each lead dialled in a dialler session
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialler_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('dialler_session_id')->constrained('dialler_sessions')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('call_log_id')->nullable()->constrained('call_logs')->nullOnDelete();
            $table->unsignedInteger('queue_order');
            $table->string('status', 20)->default('QUEUED');
            $table->string('failure_reason')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'dialler_session_id']);
            $table->index(['dialler_session_id', 'status']);
            $table->index(['dialler_session_id', 'queue_order']);
            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialler_logs');
    }
};
