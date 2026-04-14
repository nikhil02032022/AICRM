<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BRD: CRM-EC-010 — Counsellor performance gamification tables
     */
    public function up(): void
    {
        // Badges definition table
        Schema::create('crm_badges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable(); // FontAwesome class or emoji
            $table->string('color', 20)->default('blue'); // badge color
            $table->enum('category', ['performance', 'milestone', 'consistency', 'excellence', 'special'])->default('performance');
            $table->json('criteria'); // JSON defining unlock criteria
            $table->integer('points')->default(0); // points awarded
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug', 'is_active']);
        });

        // Counsellor performance scores table
        Schema::create('crm_gamification_scores', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // counsellor
            
            // KPI metrics
            $table->integer('leads_handled')->default(0);
            $table->integer('leads_converted')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0); // percentage
            $table->integer('avg_response_time_minutes')->default(0);
            $table->decimal('student_satisfaction_score', 3, 2)->default(0); // out of 5.00
            $table->integer('calls_made')->default(0);
            $table->integer('emails_sent')->default(0);
            $table->integer('meetings_scheduled')->default(0);
            $table->integer('applications_submitted')->default(0);
            
            // Gamification metrics
            $table->integer('total_points')->default(0);
            $table->integer('streak_days')->default(0); // consecutive days active
            $table->date('last_activity_date')->nullable();
            
            // Period tracking
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('period_start');
            $table->date('period_end');
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'period_type', 'period_start'], 'user_period_unique');
            $table->index(['institution_id', 'campus_id', 'period_type', 'period_start'], 'gs_inst_camp_period_idx');
            $table->index(['user_id', 'period_type', 'total_points']);
        });

        // Leaderboards table
        Schema::create('crm_leaderboards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // counsellor
            
            $table->integer('rank')->default(0);
            $table->integer('total_points')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->integer('leads_converted')->default(0);
            
            // Period tracking
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('period_start');
            $table->date('period_end');
            
            // Ranking metadata
            $table->integer('rank_change')->default(0); // +/- from previous period
            $table->enum('trend', ['up', 'down', 'stable'])->default('stable');
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'period_type', 'period_start'], 'leaderboard_user_period_unique');
            $table->index(['institution_id', 'campus_id', 'period_type', 'period_start', 'rank'], 'lb_inst_camp_period_rank_idx');
        });

        // Counsellor badges earned (pivot table)
        Schema::create('crm_counsellor_badges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // counsellor
            $table->foreignId('badge_id')->constrained('crm_badges')->cascadeOnDelete();
            
            $table->integer('points_earned')->default(0);
            $table->timestamp('earned_at');
            $table->json('criteria_met')->nullable(); // JSON snapshot of criteria when earned
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'badge_id'], 'counsellor_badge_unique');
            $table->index(['institution_id', 'user_id', 'earned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_counsellor_badges');
        Schema::dropIfExists('crm_leaderboards');
        Schema::dropIfExists('crm_gamification_scores');
        Schema::dropIfExists('crm_badges');
    }
};
