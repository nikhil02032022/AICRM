<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TC-002 — Configurable call scripts with response-based branching
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_scripts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->string('name', 140);
            $table->string('status', 20)->default('draft');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'status']);
            $table->unique(['institution_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_scripts');
    }
};
