<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pii_erasure_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('scheduled_erasure_at')->nullable();
            $table->timestamp('erased_at')->nullable();
            $table->boolean('erased_by_job')->default(false);
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index(['status', 'scheduled_erasure_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pii_erasure_requests');
    }
};
