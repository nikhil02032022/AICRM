<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AL-004 — Alumni NPS snapshot storage for analytics dashboard
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_nps_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->foreign('programme_id')->references('id')->on('crm_programmes')->nullOnDelete();
            $table->smallInteger('nps_score'); // promoters_pct - detractors_pct (-100 to +100)
            $table->decimal('promoters_pct', 5, 2);
            $table->decimal('neutrals_pct', 5, 2);
            $table->decimal('detractors_pct', 5, 2);
            $table->date('survey_date');
            $table->string('source', 20)->default('manual'); // manual | webhook
            $table->timestamps();

            $table->index(['institution_id', 'academic_year_id']);
            $table->index(['institution_id', 'survey_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_nps_snapshots');
    }
};
