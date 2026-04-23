<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AG-001 — Agent/channel partner profile store
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('mobile')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->date('agreement_start');
            $table->date('agreement_end')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Email must be unique within an institution
            $table->unique(['institution_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
