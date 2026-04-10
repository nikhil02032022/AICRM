<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EC-001 — Lead/enquiry record captures full academic background
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            // Academic background — CRM-EC-001
            $table->string('qualification', 80)->nullable()->after('notes');
            $table->decimal('marks_10th', 5, 2)->nullable()->after('qualification');
            $table->string('board_10th', 100)->nullable()->after('marks_10th');
            $table->decimal('marks_12th', 5, 2)->nullable()->after('board_10th');
            $table->string('board_12th', 100)->nullable()->after('marks_12th');
            $table->decimal('graduation_percentage', 5, 2)->nullable()->after('board_12th');
            $table->string('graduation_university', 150)->nullable()->after('graduation_percentage');
            // Preferred intake — stored as "YYYY-MM" e.g. "2026-07"
            $table->string('preferred_intake', 10)->nullable()->after('graduation_university');
            $table->date('date_of_birth')->nullable()->after('preferred_intake');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'qualification',
                'marks_10th',
                'board_10th',
                'marks_12th',
                'board_12th',
                'graduation_percentage',
                'graduation_university',
                'preferred_intake',
                'date_of_birth',
            ]);
        });
    }
};
