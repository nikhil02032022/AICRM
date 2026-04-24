<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('label', 30);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('rolled_over_from_id')->nullable();
            $table->foreign('rolled_over_from_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['institution_id', 'is_active']);
            $table->unique(['institution_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
