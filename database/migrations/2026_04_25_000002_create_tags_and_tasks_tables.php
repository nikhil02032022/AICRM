<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tags', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->string('name', 100);
            $table->string('color', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institution_id', 'name']);
        });

        Schema::create('lead_tag', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('institution_id')->index();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('crm_tag_id')->constrained('crm_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lead_id', 'crm_tag_id']);
            $table->index(['institution_id', 'lead_id']);
        });

        Schema::create('crm_tasks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('status', 40)->default('open')->index();
            $table->timestamp('due_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('lead_tag');
        Schema::dropIfExists('crm_tags');
    }
};
