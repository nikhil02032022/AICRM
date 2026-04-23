<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AR-001, AR-006 — Pre-aggregated daily metric snapshots for fast dashboard loads (< 3s target)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_metric_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->date('period_date');
            $table->string('metric_key', 100);      // e.g. leads_total, applications_total, revenue_total
            $table->decimal('metric_value', 15, 2)->default(0);
            $table->json('segmentation_json')->nullable(); // {programme_id, source, counsellor_id, ...}
            $table->timestamps();

            $table->index(['institution_id', 'period_date', 'metric_key'], 'idx_snapshots_lookup');
            $table->index(['institution_id', 'campus_id', 'period_date'], 'idx_snapshots_campus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_metric_snapshots');
    }
};
