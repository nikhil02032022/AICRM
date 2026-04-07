<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-006 — Leads can express interest in one or more programmes
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_programme_interests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('crm_programme_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['lead_id', 'crm_programme_id']);
            $table->index('lead_id');
            $table->index('crm_programme_id');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_programme_interests');
    }
};
