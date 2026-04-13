<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TC-003 — Configurable call dispositions with follow-up behavior flags
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_disposition_configs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->string('code', 40); // must map to CallDisposition enum values
            $table->string('label', 120);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_follow_up')->default(false); // BRD: CRM-TC-004
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_system')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institution_id', 'code']);
            $table->index(['institution_id', 'is_active']);
            $table->index(['institution_id', 'requires_follow_up']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_disposition_configs');
    }
};
