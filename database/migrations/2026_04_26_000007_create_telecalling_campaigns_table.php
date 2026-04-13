<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TC-006 — Calling campaign management core table
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telecalling_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->timestamp('start_time_window')->nullable();
            $table->timestamp('end_time_window')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('launched_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'start_time_window']);
            $table->unique(['institution_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telecalling_campaigns');
    }
};
