<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opt_out_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('channel', 20)->default('all');
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->boolean('processed_by_job')->default(false);
            $table->timestamps();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index(['processed_at', 'processed_by_job']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opt_out_logs');
    }
};
