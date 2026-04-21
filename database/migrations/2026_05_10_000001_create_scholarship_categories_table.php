<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-006 — Configurable scholarship and fee waiver categories
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_categories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('programme_id')->nullable();

            $table->string('code', 40);
            $table->string('name', 120);
            $table->string('type', 30);            // ScholarshipType enum
            $table->string('computation', 10);     // 'percent' | 'flat'
            $table->decimal('value', 12, 2);
            $table->decimal('max_cap', 12, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institution_id', 'code']);
            $table->index('institution_id');
            $table->index(['institution_id', 'is_active']);
            $table->index('programme_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_categories');
    }
};
