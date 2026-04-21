<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-007 — Scholarship eligibility evaluation rules
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_eligibility_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('scholarship_category_id');

            $table->string('attribute', 80);     // whitelisted (see config)
            $table->string('operator', 10);      // gte, lte, eq, in, between
            $table->json('value');
            $table->string('combinator', 4)->default('AND');
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('scholarship_category_id');
            $table->index('institution_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_eligibility_rules');
    }
};
