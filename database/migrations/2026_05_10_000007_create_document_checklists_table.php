<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-DM-001 — Programme-wise document checklists
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_checklists', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('programme_id')->nullable();

            $table->string('name', 120);
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('programme_id');
            $table->index(['institution_id', 'programme_id', 'is_active'], 'dc_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_checklists');
    }
};
