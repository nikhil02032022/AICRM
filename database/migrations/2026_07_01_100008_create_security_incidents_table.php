<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('incident_type', 100);
            $table->text('description');
            $table->timestamp('detected_at');
            $table->timestamp('notified_at')->nullable();
            $table->string('status', 30)->default('reported');
            $table->json('documentation_json')->nullable();
            $table->timestamps();

            $table->index('institution_id');
            $table->index('status');
            $table->index(['institution_id', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
    }
};
