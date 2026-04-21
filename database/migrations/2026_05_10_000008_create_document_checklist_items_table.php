<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-DM-001 — Individual document items per checklist
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('document_checklist_id');

            $table->string('code', 80);          // e.g. 10TH_MARKSHEET
            $table->string('label', 200);
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedInteger('max_size_kb')->nullable();
            $table->json('allowed_mime')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['document_checklist_id', 'code']);
            $table->index('institution_id');
            $table->index('document_checklist_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_checklist_items');
    }
};
