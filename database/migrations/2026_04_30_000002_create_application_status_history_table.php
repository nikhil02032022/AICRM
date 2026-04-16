<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-009 — Full audit trail of all application status transitions
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_status_history', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy
            $table->unsignedBigInteger('institution_id');

            // Relationships
            $table->uuid('application_uuid');
            $table->unsignedBigInteger('changed_by_user_id')->nullable();

            // State change tracking
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for audit lookups
            $table->index('institution_id');
            $table->index('application_uuid');
            $table->index('changed_by_user_id');
            $table->index('created_at');
            $table->index(['application_uuid', 'created_at']);
            $table->index(['institution_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_history');
    }
};
