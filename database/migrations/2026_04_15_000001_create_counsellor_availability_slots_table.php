<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EC-015 — Counsellor availability slots table
// BRD: CRM-EC-016 — Stores recurring + one-off availability windows
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counsellor_availability_slots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counsellor_id')->constrained('users')->cascadeOnDelete();

            // Day-of-week (0=Sunday…6=Saturday) for recurring; null for one-off
            $table->tinyInteger('day_of_week')->nullable()->index();

            // One-off specific date (null for recurring)
            $table->date('slot_date')->nullable()->index();

            $table->time('start_time');
            $table->time('end_time');

            // Duration each booking occupies (minutes)
            $table->unsignedSmallInteger('slot_duration_minutes')->default(30);

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->index(['institution_id', 'counsellor_id', 'is_active'], 'avail_institution_counsellor_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counsellor_availability_slots');
    }
};
