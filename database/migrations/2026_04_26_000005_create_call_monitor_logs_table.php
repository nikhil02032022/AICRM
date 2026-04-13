<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TC-005 — Supervisor listen/whisper/barge-in monitoring audit log
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_monitor_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('call_log_id')->index();
            $table->unsignedBigInteger('supervisor_id')->index();
            $table->string('mode', 20); // LISTEN, WHISPER, BARGE_IN
            $table->string('status', 20)->default('ACTIVE'); // ACTIVE, ENDED, FAILED
            $table->string('provider_session_id', 120)->nullable();
            $table->boolean('consent_validated')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'status']);
            $table->index(['call_log_id', 'status']);
            $table->index(['supervisor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_monitor_logs');
    }
};
