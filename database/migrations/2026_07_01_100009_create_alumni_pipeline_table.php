<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_pipeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('programme_id')->constrained('crm_programmes')->cascadeOnDelete();
            $table->timestamp('graduated_at')->nullable();
            $table->string('alumni_status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['lead_id', 'application_id']);
            $table->index('institution_id');
            $table->index(['institution_id', 'alumni_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_pipeline');
    }
};
