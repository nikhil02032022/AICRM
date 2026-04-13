<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-005 — Persist daily counsellor priority lead snapshots
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counsellor_priority_leads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('counsellor_id');
            $table->unsignedBigInteger('lead_id');
            $table->unsignedTinyInteger('priority_rank');
            $table->unsignedTinyInteger('priority_score');
            $table->text('reasoning');
            $table->json('factors')->nullable();
            $table->date('generated_for_date');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index('institution_id');
            $table->index('counsellor_id');
            $table->index('lead_id');
            $table->index('generated_for_date');
            $table->index(['institution_id', 'counsellor_id', 'generated_for_date'], 'cpl_inst_counsellor_date_idx');
            $table->unique(['counsellor_id', 'lead_id', 'generated_for_date'], 'cpl_unique_daily');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');

            $table->foreign('counsellor_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counsellor_priority_leads');
    }
};
