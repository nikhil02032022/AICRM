<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('delivery_method', 20)->default('email');
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_access_requests');
    }
};
